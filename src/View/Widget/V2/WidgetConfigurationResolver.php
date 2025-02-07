<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget\V2;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\View\Widget\FrameStyle;
use izi\prestashop\View\Widget\Variant;
use izi\prestashop\View\Widget\WidgetConfigurationInterface;
use izi\prestashop\View\Widget\WidgetConfigurationResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @implements WidgetConfigurationResolverInterface<WidgetConfiguration>
 */
final class WidgetConfigurationResolver implements WidgetConfigurationResolverInterface
{
    /**
     * @var OptionsResolver|null
     */
    private $optionsResolver;

    public function resolve(array $options): WidgetConfigurationInterface
    {
        if (isset($options['config']) && $options['config'] instanceof WidgetConfigurationInterface) {
            return $options['config'];
        }

        $options = $this
            ->getOptionsResolver()
            ->setDefined(array_keys($options))
            ->resolve($options);

        return (new WidgetConfiguration($options['binding_place']))
            ->setVariant($options['variant'])
            ->setDarkMode($options['dark_mode'])
            ->setFrameStyle($options['frame_style'])
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
                'variant' => Variant::Secondary(),
                'dark_mode' => false,
                'frame_style' => null,
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
            ->setAllowedTypes('variant', [Variant::class, 'string'])
            ->setNormalizer('variant', static function (Options $options, $value) {
                if ($value instanceof Variant) {
                    return $value;
                }

                return Variant::from($value);
            })
            ->setAllowedTypes('dark_mode', 'bool')
            ->setAllowedTypes('frame_style', [FrameStyle::class, 'string', 'null'])
            ->setNormalizer('frame_style', static function (Options $options, $value) {
                if (null === $value || $value instanceof FrameStyle) {
                    return $value;
                }

                return FrameStyle::from($value);
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

    private static function isValidWidth($width): bool
    {
        if (null === $width) {
            return true;
        }

        $width = (int) $width;

        return WidgetConfiguration::WIDTH_MIN_PX <= $width && WidgetConfiguration::WIDTH_MAX_PX >= $width;
    }
}
