<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\View\Widget\Configuration;
use Symfony\Component\Validator\Constraints as Assert;

final class GuiConfiguration implements GuiConfigurationInterface
{
    /**
     * @var bool|null
     */
    private $widgetDisplayedOnCartPage;

    /**
     * @var Configuration|null
     *
     * @Assert\NotNull()
     * @Assert\Valid()
     */
    private $cartPageWidgetConfiguration;

    /**
     * @var HtmlStyles|null
     *
     * @Assert\NotNull()
     * @Assert\Valid()
     */
    private $cartPageHtmlStyles;

    /**
     * @var bool|null
     */
    private $widgetDisplayedOnProductCard;

    /**
     * @var Configuration|null
     *
     * @Assert\NotNull()
     * @Assert\Valid()
     */
    private $productCardWidgetConfiguration;

    public function __construct(bool $widgetDisplayedOnCartPage = false, Configuration $cartPageWidgetConfiguration = null, HtmlStyles $cartPageHtmlStyles = null, bool $widgetDisplayedOnProductCard = false, Configuration $productCardWidgetConfiguration = null)
    {
        $this->widgetDisplayedOnCartPage = $widgetDisplayedOnCartPage;
        $this->cartPageWidgetConfiguration = $cartPageWidgetConfiguration;
        $this->cartPageHtmlStyles = $cartPageHtmlStyles;
        $this->widgetDisplayedOnProductCard = $widgetDisplayedOnProductCard;
        $this->productCardWidgetConfiguration = $productCardWidgetConfiguration;
    }

    public function getCheckoutWidgetConfiguration(): Configuration
    {
        return $this->getCartPageWidgetConfiguration();
    }

    public function isWidgetDisplayedOnCartPage(): bool
    {
        return true === $this->widgetDisplayedOnCartPage;
    }

    public function setWidgetDisplayedOnCartPage(?bool $widgetDisplayedOnCartPage): self
    {
        $this->widgetDisplayedOnCartPage = $widgetDisplayedOnCartPage;

        return $this;
    }

    public function getCartPageWidgetConfiguration(): Configuration
    {
        return $this->cartPageWidgetConfiguration ?? new Configuration(BindingPlace::BasketSummary(), true);
    }

    public function setCartPageWidgetConfiguration(?Configuration $cartPageWidgetConfiguration): self
    {
        $this->cartPageWidgetConfiguration = $cartPageWidgetConfiguration;

        return $this;
    }

    public function getCartPageHtmlStyles(): HtmlStyles
    {
        return $this->cartPageHtmlStyles ?? new HtmlStyles();
    }

    public function setCartPageHtmlStyles(?HtmlStyles $cartPageHtmlStyles): self
    {
        $this->cartPageHtmlStyles = $cartPageHtmlStyles;

        return $this;
    }

    public function isWidgetDisplayedOnProductCard(): bool
    {
        return true === $this->widgetDisplayedOnProductCard;
    }

    public function setWidgetDisplayedOnProductCard(?bool $widgetDisplayedOnProductCard): self
    {
        $this->widgetDisplayedOnProductCard = $widgetDisplayedOnProductCard;

        return $this;
    }

    public function getProductCardWidgetConfiguration(): Configuration
    {
        return $this->productCardWidgetConfiguration ?? new Configuration(BindingPlace::ProductCard());
    }

    public function setProductCardWidgetConfiguration(?Configuration $productCardWidgetConfiguration): self
    {
        $this->productCardWidgetConfiguration = $productCardWidgetConfiguration;

        return $this;
    }
}
