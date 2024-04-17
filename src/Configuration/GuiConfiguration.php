<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\DTO\HtmlStyles;
use izi\prestashop\Configuration\DTO\WidgetDisplayConfiguration;
use izi\prestashop\View\Widget\Configuration;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class GuiConfiguration implements GuiConfigurationInterface, PersistentConfigurationInterface
{
    private const BASKET_SUMMARY_WIDGET_DISPLAY = 'INPOST_PAY_show_button_cart';
    private const BASKET_SUMMARY_WIDGET_CONFIG = 'INPOST_PAY_CART_WIDGET_CONFIG';
    private const BASKET_SUMMARY_HTML_STYLES = 'INPOST_PAY_CART_HTML_STYLES';

    private const PRODUCT_CARD_WIDGET_DISPLAY = 'INPOST_PAY_show_button_details';
    private const PRODUCT_CARD_WIDGET_CONFIG = 'INPOST_PAY_PRODUCT_CARD_WIDGET_CONFIG';
    private const PRODUCT_CARD_HTML_STYLES = 'INPOST_PAY_PRODUCT_HTML_STYLES';

    private const LOGIN_PAGE_WIDGET_DISPLAY = 'INPOST_PAY_SHOW_LOGIN_PAGE_WIDGET';
    private const LOGIN_PAGE_WIDGET_CONFIG = 'INPOST_PAY_LOGIN_PAGE_WIDGET_CONFIG';
    private const LOGIN_PAGE_HTML_STYLES = 'INPOST_PAY_LOGIN_PAGE_HTML_STYLES';

    private const REGISTERFORM_PAGE_WIDGET_DISPLAY = 'INPOST_PAY_SHOW_REGISTERFORM_PAGE_WIDGET';
    private const REGISTERFORM_PAGE_WIDGET_CONFIG = 'INPOST_PAY_REGISTERFORM_PAGE_WIDGET_CONFIG';
    private const REGISTERFORM_PAGE_HTML_STYLES = 'INPOST_PAY_REGISTERFORM_PAGE_HTML_STYLES';

    private const CHECKOUT_PAGE_WIDGET_DISPLAY = 'INPOST_PAY_SHOW_CHECKOUT_PAGE_WIDGET';
    private const CHECKOUT_PAGE_WIDGET_CONFIG = 'INPOST_PAY_CHECKOUT_PAGE_WIDGET_CONFIG';
    private const CHECKOUT_PAGE_HTML_STYLES = 'INPOST_PAY_CHECKOUT_PAGE_HTML_STYLES';

    private const MINICART_PAGE_WIDGET_DISPLAY = 'INPOST_PAY_SHOW_MINICART_PAGE_WIDGET';
    private const MINICART_PAGE_WIDGET_CONFIG = 'INPOST_PAY_MINICART_PAGE_WIDGET_CONFIG';
    private const MINICART_PAGE_HTML_STYLES = 'INPOST_PAY_MINICART_PAGE_HTML_STYLES';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    private $loadedConfiguration = [];

    public function __construct(ConfigurationInterface $configuration, SerializerInterface $serializer)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
    }

    // TODO separate config?
    public function getCheckoutWidgetConfiguration(): Configuration
    {
        $widgetDisplayConfiguration = $this->getCartWidgetDisplayConfiguration();
        $config = $widgetDisplayConfiguration->getWidgetConfiguration();

        return $config->setBindingPlace(BindingPlace::OrderCreate());
    }

    public function copy(): GuiConfigurationInterface
    {
        return new DTO\GuiConfiguration(
            $this->getCartWidgetDisplayConfiguration(),
            $this->getProductWidgetDisplayConfiguration(),
            $this->getLoginPageWidgetDisplayConfiguration(),
            $this->getRegisterFormPageWidgetDisplayConfiguration(),
            $this->getCheckoutPageWidgetDisplayConfiguration(),
            $this->getMiniCartPageWidgetDisplayConfiguration()
        );
    }

    public function persist(GuiConfigurationInterface $configuration): void
    {
        $this->setWidgetDisplayConfiguration($configuration->getCartWidgetDisplayConfiguration());
        $this->setWidgetDisplayConfiguration($configuration->getProductWidgetDisplayConfiguration());
        $this->setWidgetDisplayConfiguration($configuration->getLoginPageWidgetDisplayConfiguration());
        $this->setWidgetDisplayConfiguration($configuration->getRegisterFormPageWidgetDisplayConfiguration());
        $this->setWidgetDisplayConfiguration($configuration->getCheckoutPageWidgetDisplayConfiguration());
        $this->setWidgetDisplayConfiguration($configuration->getMiniCartPageWidgetDisplayConfiguration());
    }

    private function setWidgetDisplayConfiguration(WidgetDisplayConfiguration $widgetDisplayConfig): void
    {
        $this->configuration->set($this->getDisplayWidgetConfigKey($widgetDisplayConfig->getBinding()), $widgetDisplayConfig->isDisplayed());
        $this->setHtmlStyles($widgetDisplayConfig->getHtmlStyles(), $widgetDisplayConfig->getBinding());
        $this->setWidgetConfiguration($widgetDisplayConfig->getWidgetConfiguration(), $widgetDisplayConfig->getBinding());
    }

    private function setHtmlStyles(HtmlStyles $styles, BindingPlace $bindingPlace): void
    {
        $value = $this->serializer->serialize($styles, 'json');
        $this->configuration->set($this->getHtmlStyleConfigKey($bindingPlace), $value);
    }

    private function setWidgetConfiguration(Configuration $config, BindingPlace $bindingPlace): void
    {
        $value = $this->serializer->serialize($config, 'json');
        $this->configuration->set($this->getConfigurationWidgetConfigKey($bindingPlace), $value);
    }

    private function loadHtmlStyles(BindingPlace $bindingPlace): HtmlStyles
    {
        $value = $this->configuration->get($this->getHtmlStyleConfigKey($bindingPlace));

        if (null !== $value && $styles = $this->deserialize($value, HtmlStyles::class)) {
            return $styles;
        }

        return new HtmlStyles();
    }

    private function isBasketByBinding(BindingPlace $bindingPlace): bool
    {
        return $bindingPlace !== BindingPlace::ProductCard();
    }

    private function loadWidgetConfiguration(BindingPlace $bindingPlace): Configuration
    {
        $value = $this->configuration->get($this->getConfigurationWidgetConfigKey($bindingPlace));

        if (null !== $value && $config = $this->deserialize($value, Configuration::class)) {
            return $config;
        }

        return new Configuration($bindingPlace, $this->isBasketByBinding($bindingPlace));
    }

    private function getWidgetDisplayConfigurationByBinding(BindingPlace $bindingPlace): WidgetDisplayConfiguration
    {
        $key = $bindingPlace->value;

        if (!isset($this->loadedConfiguration[$key])) {
            $this->loadedConfiguration[$key] = $this->loadWidgetDisplayConfigurationByBinding($bindingPlace);
        }

        return $this->loadedConfiguration[$key];
    }

    private function loadWidgetDisplayConfigurationByBinding(BindingPlace $binding): WidgetDisplayConfiguration
    {
        $htmlStyles = $this->loadHtmlStyles($binding);
        $configuration = $this->loadWidgetConfiguration($binding);
        $displayed = (bool) $this->configuration->get($this->getDisplayWidgetConfigKey($binding));

        return new WidgetDisplayConfiguration($binding, $displayed, $configuration, $htmlStyles);
    }

    public function getCartWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return clone $this->getWidgetDisplayConfigurationByBinding(BindingPlace::BasketSummary());
    }

    public function getProductWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return clone $this->getWidgetDisplayConfigurationByBinding(BindingPlace::ProductCard());
    }

    public function getLoginPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return clone $this->getWidgetDisplayConfigurationByBinding(BindingPlace::LoginPage());
    }

    public function getRegisterFormPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return clone $this->getWidgetDisplayConfigurationByBinding(BindingPlace::RegisterFormPage());
    }

    public function getCheckoutPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return clone $this->getWidgetDisplayConfigurationByBinding(BindingPlace::CheckoutPage());
    }

    public function getMiniCartPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return clone $this->getWidgetDisplayConfigurationByBinding(BindingPlace::MiniCartPage());
    }

    private function getHtmlStyleConfigKey(BindingPlace $bindingPlace): string
    {
        $constantName = $bindingPlace->value . '_HTML_STYLES';
        $classNamespace = self::class;

        if (!defined($classNamespace . '::' . $constantName)) {
            throw new \InvalidArgumentException('Invalid BindingPlace enum value.');
        }

        return constant($classNamespace . '::' . $constantName);
    }

    private function getDisplayWidgetConfigKey(BindingPlace $bindingPlace): string
    {
        $constantName = $bindingPlace->value . '_WIDGET_DISPLAY';
        $classNamespace = self::class;

        if (!defined($classNamespace . '::' . $constantName)) {
            throw new \InvalidArgumentException('Invalid BindingPlace enum value.');
        }

        return constant($classNamespace . '::' . $constantName);
    }

    private function getConfigurationWidgetConfigKey(BindingPlace $bindingPlace): string
    {
        $constantName = $bindingPlace->value . '_WIDGET_CONFIG';
        $classNamespace = self::class;

        if (!defined($classNamespace . '::' . $constantName)) {
            throw new \InvalidArgumentException('Invalid BindingPlace enum value.');
        }

        return constant($classNamespace . '::' . $constantName);
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    private function deserialize(string $value, string $class)
    {
        try {
            return $this->serializer->deserialize($value, $class, 'json');
        } catch (ExceptionInterface $e) {
            return null;
        }
    }
}
