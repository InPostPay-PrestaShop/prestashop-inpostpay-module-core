<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\BasketApp\Basket\Response\ClientDetails;
use izi\prestashop\Command\GetClientDetailsCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Payment\PaymentCurrencyChecker;
use izi\prestashop\View\Widget\Alignment;
use izi\prestashop\View\Widget\Configuration;
use izi\prestashop\View\Widget\FrameStyle;
use izi\prestashop\View\Widget\Language;
use izi\prestashop\View\Widget\Variant;
use izi\prestashop\View\Widget\WidgetConfigurationInterface;
use izi\prestashop\View\Widget\WidgetConfigurationResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @implements WidgetConfigurationResolverInterface<Configuration>
 *
 * @deprecated
 */
final class WidgetConfigurationResolver implements WidgetConfigurationResolverInterface
{
    /**
     * @var ApiConfigurationInterface
     */
    private $configuration;

    /**
     * @var \PaymentModule
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var PaymentCurrencyChecker
     */
    private $currencyChecker;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var OptionsResolver|null
     */
    private $optionsResolver;

    private $cache = [];

    public function __construct(ApiConfigurationInterface $configuration, \PaymentModule $module, \Context $context, PaymentCurrencyChecker $currencyChecker, CommandBusInterface $bus)
    {
        $this->configuration = $configuration;
        $this->module = $module;
        $this->context = $context;
        $this->currencyChecker = $currencyChecker;
        $this->bus = $bus;
    }

    public function resolve(array $options): ?WidgetConfigurationInterface
    {
        if (null === $this->configuration->getClientCredentials()) {
            return null;
        }

        $configuration = $this->doResolve($options);

        try {
            $clientDetails = $this->getClientDetails();
        } catch (\Exception $e) {
            return null;
        }

        if (null === $clientDetails && !$this->currencyChecker->check($this->module, (int) $this->context->currency->id)) {
            return null;
        }

        $count = $this->getCartProductsCount();

        if (null === $clientDetails && 0 >= $count && $configuration->isBasket()) {
            return null;
        }

        $configuration
            ->setCount($count)
            ->setLanguage(Language::tryFrom($this->context->language->iso_code) ?? Language::En());

        if (null === $clientDetails) {
            return $configuration;
        }

        return $configuration
            ->setName($clientDetails->getName())
            ->setMaskedPhoneNumber($clientDetails->getMaskedPhoneNumber());
    }

    private function doResolve(array $options): Configuration
    {
        if (isset($options['config']) && $options['config'] instanceof Configuration) {
            return $options['config'];
        }

        $options = $this
            ->getOptionsResolver()
            ->setDefined(array_keys($options))
            ->resolve($options);

        return (new Configuration($options['binding_place'], $options['basket']))
            ->setVariant($options['variant'])
            ->setDarkMode($options['dark_mode'])
            ->setAlignment($options['alignment'])
            ->setFrameStyle($options['frame_style'])
            ->setMinWidthPx($options['min_width'])
            ->setMaxWidthPx($options['max_width'])
            ->setProductId($options['product_id']);
    }

    private function getOptionsResolver(): OptionsResolver
    {
        return $this->optionsResolver ?? ($this->optionsResolver = $this->createOptionsResolver());
    }

    private function createOptionsResolver(): OptionsResolver
    {
        return (new OptionsResolver())
            ->setDefaults([
                'binding_place' => BindingPlace::ProductCard(),
                'basket' => false,
                'variant' => Variant::Secondary(),
                'dark_mode' => false,
                'alignment' => null,
                'frame_style' => null,
                'min_width' => null,
                'max_width' => null,
                'product_id' => null,
            ])
            ->setAllowedTypes('binding_place', [BindingPlace::class, 'string'])
            ->setNormalizer('binding_place', static function (Options $options, $value) {
                if ($value instanceof BindingPlace) {
                    return $value;
                }

                return BindingPlace::from($value);
            })
            ->setAllowedTypes('basket', 'bool')
            ->setAllowedTypes('variant', [Variant::class, 'string'])
            ->setNormalizer('variant', static function (Options $options, $value) {
                if ($value instanceof Variant) {
                    return $value;
                }

                return Variant::from($value);
            })
            ->setAllowedTypes('dark_mode', 'bool')
            ->setAllowedTypes('alignment', [Alignment::class, 'string', 'null'])
            ->setNormalizer('alignment', static function (Options $options, $value) {
                if (null === $value || $value instanceof Alignment) {
                    return $value;
                }

                return Alignment::from($value);
            })
            ->setAllowedTypes('frame_style', [FrameStyle::class, 'string', 'null'])
            ->setNormalizer('frame_style', static function (Options $options, $value) {
                if (null === $value || $value instanceof FrameStyle) {
                    return $value;
                }

                return FrameStyle::from($value);
            })
            ->setAllowedValues('min_width', static function ($value) {
                return self::isValidWidth($value);
            })
            ->setAllowedValues('max_width', static function ($value) {
                return self::isValidWidth($value);
            })
            ->setAllowedTypes('product_id', ['string', 'int', 'null'])
            ->setNormalizer('product_id', static function (Options $options, $value) {
                if (null === $value) {
                    return null;
                }

                return (string) $value;
            });
    }

    private function getClientDetails(): ?ClientDetails
    {
        if (array_key_exists('client_details', $this->cache)) {
            return $this->cache['client_details'];
        }

        if (!\Validate::isLoadedObject($this->context->cart)) {
            return null;
        }

        $command = new GetClientDetailsCommand((int) $this->context->cart->id);

        return $this->cache['client_details'] = $this->bus->handle($command);
    }

    private function getCartProductsCount(): ?int
    {
        if (!\Validate::isLoadedObject($this->context->cart)) {
            return null;
        }

        return array_reduce($this->context->cart->getProducts(), static function ($count, array $product) {
            return $count + (int) $product['cart_quantity'];
        }, 0);
    }

    private static function isValidWidth($width): bool
    {
        if (null === $width) {
            return true;
        }

        $width = (int) $width;

        return Configuration::WIDTH_MIN_PX <= $width && Configuration::WIDTH_MAX_PX >= $width;
    }
}
