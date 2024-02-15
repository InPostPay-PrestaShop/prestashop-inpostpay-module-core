<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\DTO\HtmlStyles;
use izi\prestashop\View\Widget\Configuration;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class GuiConfiguration implements GuiConfigurationInterface, PersistentConfigurationInterface
{
    private const ENABLE_CART_PAGE_WIDGET_DISPLAY = 'INPOST_PAY_show_button_cart';
    private const CART_PAGE_WIDGET_CONFIG = 'INPOST_PAY_CART_WIDGET_CONFIG';
    private const CART_PAGE_HTML_STYLES = 'INPOST_PAY_CART_HTML_STYLES';
    private const ENABLE_PRODUCT_CARD_WIDGET_DISPLAY = 'INPOST_PAY_show_button_details';
    private const PRODUCT_CARD_WIDGET_CONFIG = 'INPOST_PAY_PRODUCT_CARD_WIDGET_CONFIG';
    private const PRODUCT_CARD_HTML_STYLES = 'INPOST_PAY_PRODUCT_HTML_STYLES';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    private $cartWidgetConfig;
    private $cartHtmlStyles;
    private $productWidgetConfig;
    private $productHtmlStyles;

    public function __construct(ConfigurationInterface $configuration, SerializerInterface $serializer)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
    }

    // TODO separate config?
    public function getCheckoutWidgetConfiguration(): Configuration
    {
        return $this
            ->getCartPageWidgetConfiguration()
            ->setBindingPlace(BindingPlace::OrderCreate());
    }

    public function isWidgetDisplayedOnCartPage(): bool
    {
        return (bool) $this->configuration->get(self::ENABLE_CART_PAGE_WIDGET_DISPLAY);
    }

    public function getCartPageWidgetConfiguration(): Configuration
    {
        if (!isset($this->cartWidgetConfig)) {
            $this->cartWidgetConfig = $this->loadCartPageWidgetConfig();
        }

        return clone $this->cartWidgetConfig;
    }

    public function getCartPageHtmlStyles(): HtmlStyles
    {
        if (!isset($this->cartHtmlStyles)) {
            $this->cartHtmlStyles = $this->loadCartPageHtmlStyles();
        }

        return clone $this->cartHtmlStyles;
    }

    public function getProductCardHtmlStyles(): HtmlStyles
    {
        if (!isset($this->productHtmlStyles)) {
            $this->productHtmlStyles = $this->loadProductCardHtmlStyles();
        }

        return clone $this->productHtmlStyles;
    }

    public function isWidgetDisplayedOnProductCard(): bool
    {
        return (bool) $this->configuration->get(self::ENABLE_PRODUCT_CARD_WIDGET_DISPLAY);
    }

    public function getProductCardWidgetConfiguration(): Configuration
    {
        if (!isset($this->productWidgetConfig)) {
            $this->productWidgetConfig = $this->loadProductCardWidgetConfig();
        }

        return clone $this->productWidgetConfig;
    }

    public function copy(): GuiConfigurationInterface
    {
        return new DTO\GuiConfiguration(
            $this->isWidgetDisplayedOnCartPage(),
            $this->getCartPageWidgetConfiguration(),
            $this->getCartPageHtmlStyles(),
            $this->isWidgetDisplayedOnProductCard(),
            $this->getProductCardWidgetConfiguration(),
            $this->getProductCardHtmlStyles()
        );
    }

    public function persist(GuiConfigurationInterface $configuration): void
    {
        $this->configuration->set(self::ENABLE_CART_PAGE_WIDGET_DISPLAY, $configuration->isWidgetDisplayedOnCartPage());
        $this->configuration->set(self::ENABLE_PRODUCT_CARD_WIDGET_DISPLAY, $configuration->isWidgetDisplayedOnProductCard());
        $this->setCartPageWidgetConfig($configuration->getCartPageWidgetConfiguration());
        $this->setCartPageHtmlStyles($configuration->getCartPageHtmlStyles());
        $this->setProductPageWidgetConfig($configuration->getProductCardWidgetConfiguration());
        $this->setProductCardHtmlStyles($configuration->getProductCardHtmlStyles());
    }

    private function loadCartPageWidgetConfig(): Configuration
    {
        $value = $this->configuration->get(self::CART_PAGE_WIDGET_CONFIG);

        if (null !== $value && $config = $this->deserialize($value, Configuration::class)) {
            return $config;
        }

        return new Configuration(BindingPlace::BasketSummary(), true);
    }

    private function loadCartPageHtmlStyles(): HtmlStyles
    {
        $value = $this->configuration->get(self::CART_PAGE_HTML_STYLES);

        if (null !== $value && $styles = $this->deserialize($value, HtmlStyles::class)) {
            return $styles;
        }

        return new HtmlStyles();
    }

    private function loadProductCardWidgetConfig(): Configuration
    {
        $value = $this->configuration->get(self::PRODUCT_CARD_WIDGET_CONFIG);

        if (null !== $value && $config = $this->deserialize($value, Configuration::class)) {
            return $config;
        }

        return new Configuration(BindingPlace::ProductCard());
    }

    private function loadProductCardHtmlStyles(): HtmlStyles
    {
        $value = $this->configuration->get(self::PRODUCT_CARD_HTML_STYLES);

        if (null !== $value && $styles = $this->deserialize($value, HtmlStyles::class)) {
            return $styles;
        }

        return new HtmlStyles();
    }

    private function setCartPageWidgetConfig(Configuration $config): void
    {
        $value = $this->serializer->serialize($config, 'json');
        $this->configuration->set(self::CART_PAGE_WIDGET_CONFIG, $value);
        $this->cartWidgetConfig = $config;
    }

    private function setCartPageHtmlStyles(HtmlStyles $styles): void
    {
        $value = $this->serializer->serialize($styles, 'json');
        $this->configuration->set(self::CART_PAGE_HTML_STYLES, $value);
        $this->cartHtmlStyles = $styles;
    }

    private function setProductPageWidgetConfig(Configuration $config): void
    {
        $value = $this->serializer->serialize($config, 'json');
        $this->configuration->set(self::PRODUCT_CARD_WIDGET_CONFIG, $value);
        $this->productWidgetConfig = $config;
    }

    private function setProductCardHtmlStyles(HtmlStyles $styles): void
    {
        $value = $this->serializer->serialize($styles, 'json');
        $this->configuration->set(self::PRODUCT_CARD_HTML_STYLES, $value);
        $this->productHtmlStyles = $styles;
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
