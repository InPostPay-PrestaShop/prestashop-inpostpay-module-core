<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

use izi\prestashop\Common\BindingPlace;

final class ConfigurationFactory implements ConfigurationFactoryInterface
{
    /**
     * @var \Context
     */
    private $context;

    public function __construct(\Context $context)
    {
        $this->context = $context;
    }

    public function createForCheckout(): Configuration
    {
        $minWidth = $this->getWidgetWidth((int) \Configuration::get('INPOST_PAY_min_width_cart'));
        $maxWidth = $this->getWidgetWidth((int) \Configuration::get('INPOST_PAY_max_width_cart'));

        return (new Configuration(BindingPlace::OrderCreate(), true))
            ->setVariant(Variant::tryFrom((string) \Configuration::get('INPOST_PAY_variant_cart')) ?? Variant::Secondary())
            ->setDarkMode((bool) \Configuration::get('INPOST_PAY_background_cart'))
            ->setAlignment(Alignment::tryFrom((string) \Configuration::get('INPOST_PAY_alignment_cart')))
            ->setFrameStyle(FrameStyle::tryFrom((string) \Configuration::get('INPOST_PAY_frame_style_cart')))
            ->setMinWidth($minWidth)
            ->setMaxWidth($maxWidth)
            ->setLanguage(Language::tryFrom($this->context->language->iso_code) ?? Language::En())
            ->setCount($this->getCartProductsCount());
    }

    public function createForCartPage(): ?Configuration
    {
        if (!\Configuration::get('INPOST_PAY_show_button_cart')) {
            return null;
        }

        return $this
            ->createForCheckout()
            ->setBindingPlace(BindingPlace::BasketSummary());
    }

    public function createForProductCard(int $productId): ?Configuration
    {
        if (!\Configuration::get('INPOST_PAY_show_button_details')) {
            return null;
        }

        $minWidth = $this->getWidgetWidth((int) \Configuration::get('INPOST_PAY_min_width_details'));
        $maxWidth = $this->getWidgetWidth((int) \Configuration::get('INPOST_PAY_max_width_details'));

        return (new Configuration(BindingPlace::ProductCard(), false))
            ->setVariant(Variant::tryFrom((string) \Configuration::get('INPOST_PAY_variant_details')) ?? Variant::Secondary())
            ->setDarkMode((bool) \Configuration::get('INPOST_PAY_background_details'))
            ->setAlignment(Alignment::tryFrom((string) \Configuration::get('INPOST_PAY_alignment_details')))
            ->setFrameStyle(FrameStyle::tryFrom((string) \Configuration::get('INPOST_PAY_frame_style_details')))
            ->setMinWidth($minWidth)
            ->setMaxWidth($maxWidth)
            ->setLanguage(Language::tryFrom($this->context->language->iso_code) ?? Language::En())
            ->setCount($this->getCartProductsCount())
            ->setProductId((string) $productId);
    }

    private function getWidgetWidth(int $width): ?int
    {
        return Configuration::WIDTH_MIN_PX <= $width && Configuration::WIDTH_MAX_PX >= $width ? $width : null;
    }

    private function getCartProductsCount(): ?int
    {
        if (!isset($this->context->cart)) {
            return null;
        }

        return array_reduce($this->context->cart->getProducts(), static function ($count, array $product) {
            return $count + (int) $product['cart_quantity'];
        }, 0);
    }
}
