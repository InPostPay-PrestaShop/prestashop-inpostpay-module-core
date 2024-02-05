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

//        $minWidth = $this->getWidgetWidth((int) $this->configuration->get('INPOST_PAY_min_width_cart'));
//        $maxWidth = $this->getWidgetWidth((int) $this->configuration->get('INPOST_PAY_max_width_cart'));
//
//        return (new Configuration(BindingPlace::BasketSummary(), true))
//            ->setVariant(Variant::tryFrom((string) $this->configuration->get('INPOST_PAY_variant_cart')) ?? Variant::Secondary())
//            ->setDarkMode((bool) $this->configuration->get('INPOST_PAY_background_cart'))
//            ->setAlignment(Alignment::tryFrom((string) $this->configuration->get('INPOST_PAY_alignment_cart')))
//            ->setFrameStyle(FrameStyle::tryFrom((string) $this->configuration->get('INPOST_PAY_frame_style_cart')))
//            ->setMinWidth($minWidth)
//            ->setMaxWidth($maxWidth);
    }

    public function getCartPageHtmlStyles(): HtmlStyles
    {
        if (!isset($this->cartHtmlStyles)) {
            $this->cartHtmlStyles = $this->loadCartPageHtmlStyles();
        }

        return clone $this->cartHtmlStyles;

//        if (0 < $marginLeft = (int) $this->configuration->get('INPOST_PAY_margin_cart_left')) {
//            yield 'margin-left' => sprintf('%dpx', $marginLeft);
//        }
//
//        if (0 < $marginRight = (int) $this->configuration->get('INPOST_PAY_margin_cart_right')) {
//            yield 'margin-right' => sprintf('%dpx', $marginRight);
//        }
//
//        if (0 < $marginTop = (int) $this->configuration->get('INPOST_PAY_margin_cart_up')) {
//            yield 'margin-top' => sprintf('%dpx', $marginTop);
//        }
//
//        if (0 < $marginBottom = (int) $this->configuration->get('INPOST_PAY_margin_cart_down')) {
//            yield 'margin-bottom' => sprintf('%dpx', $marginBottom);
//        }
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

//        $minWidth = $this->getWidgetWidth((int) $this->configuration->get('INPOST_PAY_min_width_details'));
//        $maxWidth = $this->getWidgetWidth((int) $this->configuration->get('INPOST_PAY_max_width_details'));
//
//        return (new Configuration(BindingPlace::ProductCard(), false))
//            ->setVariant(Variant::tryFrom((string) $this->configuration->get('INPOST_PAY_variant_details')) ?? Variant::Secondary())
//            ->setDarkMode((bool) $this->configuration->get('INPOST_PAY_background_details'))
//            ->setAlignment(Alignment::tryFrom((string) $this->configuration->get('INPOST_PAY_alignment_details')))
//            ->setFrameStyle(FrameStyle::tryFrom((string) $this->configuration->get('INPOST_PAY_frame_style_details')))
//            ->setMinWidth($minWidth)
//            ->setMaxWidth($maxWidth);
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

//    private function getWidgetWidth(int $width): ?int
//    {
//        return Configuration::WIDTH_MIN_PX <= $width && Configuration::WIDTH_MAX_PX >= $width ? $width : null;
//    }
}
