<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\View\Widget\Alignment;
use izi\prestashop\View\Widget\Configuration;
use izi\prestashop\View\Widget\FrameStyle;
use izi\prestashop\View\Widget\Variant;
use Symfony\Component\Serializer\SerializerInterface;

final class WidgetConfiguration implements WidgetConfigurationInterface
{
    private const ENABLE_CART_PAGE_DISPLAY = 'INPOST_PAY_show_button_cart';
    private const ENABLE_PRODUCT_CARD_DISPLAY = 'INPOST_PAY_show_button_details';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(ConfigurationInterface $configuration, SerializerInterface $serializer)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
    }

    public function isVisibleToEveryone(): bool
    {
        return 2 === (int) $this->configuration->get('INPOST_PAY_show_izi');
    }

    // TODO separate config?
    public function getCheckoutConfiguration(): Configuration
    {
        return $this
            ->getCartPageConfiguration()
            ->setBindingPlace(BindingPlace::OrderCreate());
    }

    public function isDisplayedOnCartPage(): bool
    {
        return (bool) $this->configuration->get(self::ENABLE_CART_PAGE_DISPLAY);
    }

    // TODO serialize and store under single key
    public function getCartPageConfiguration(): Configuration
    {
        $minWidth = $this->getWidgetWidth((int) $this->configuration->get('INPOST_PAY_min_width_cart'));
        $maxWidth = $this->getWidgetWidth((int) $this->configuration->get('INPOST_PAY_max_width_cart'));

        return (new Configuration(BindingPlace::BasketSummary(), true))
            ->setVariant(Variant::tryFrom((string) $this->configuration->get('INPOST_PAY_variant_cart')) ?? Variant::Secondary())
            ->setDarkMode((bool) $this->configuration->get('INPOST_PAY_background_cart'))
            ->setAlignment(Alignment::tryFrom((string) $this->configuration->get('INPOST_PAY_alignment_cart')))
            ->setFrameStyle(FrameStyle::tryFrom((string) $this->configuration->get('INPOST_PAY_frame_style_cart')))
            ->setMinWidth($minWidth)
            ->setMaxWidth($maxWidth);
    }

    public function getCartPageHtmlStyles(): iterable
    {
        if (0 < $marginLeft = (int) $this->configuration->get('INPOST_PAY_margin_cart_left')) {
            yield 'margin-left' => sprintf('%dpx', $marginLeft);
        }

        if (0 < $marginRight = (int) $this->configuration->get('INPOST_PAY_margin_cart_right')) {
            yield 'margin-right' => sprintf('%dpx', $marginRight);
        }

        if (0 < $marginTop = (int) $this->configuration->get('INPOST_PAY_margin_cart_up')) {
            yield 'margin-top' => sprintf('%dpx', $marginTop);
        }

        if (0 < $marginBottom = (int) $this->configuration->get('INPOST_PAY_margin_cart_down')) {
            yield 'margin-bottom' => sprintf('%dpx', $marginBottom);
        }
    }

    public function isDisplayedOnProductCard(): bool
    {
        return (bool) $this->configuration->get(self::ENABLE_PRODUCT_CARD_DISPLAY);
    }

    // TODO serialize and store under single key
    public function getProductCardConfiguration(): Configuration
    {
        $minWidth = $this->getWidgetWidth((int) $this->configuration->get('INPOST_PAY_min_width_details'));
        $maxWidth = $this->getWidgetWidth((int) $this->configuration->get('INPOST_PAY_max_width_details'));

        return (new Configuration(BindingPlace::ProductCard(), false))
            ->setVariant(Variant::tryFrom((string) $this->configuration->get('INPOST_PAY_variant_details')) ?? Variant::Secondary())
            ->setDarkMode((bool) $this->configuration->get('INPOST_PAY_background_details'))
            ->setAlignment(Alignment::tryFrom((string) $this->configuration->get('INPOST_PAY_alignment_details')))
            ->setFrameStyle(FrameStyle::tryFrom((string) $this->configuration->get('INPOST_PAY_frame_style_details')))
            ->setMinWidth($minWidth)
            ->setMaxWidth($maxWidth);
    }

    private function getWidgetWidth(int $width): ?int
    {
        return Configuration::WIDTH_MIN_PX <= $width && Configuration::WIDTH_MAX_PX >= $width ? $width : null;
    }
}
