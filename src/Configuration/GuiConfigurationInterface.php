<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Configuration\DTO\HtmlStyles;
use izi\prestashop\View\Widget\Configuration;

interface GuiConfigurationInterface
{
    public function getCheckoutWidgetConfiguration(): Configuration;

    public function isWidgetDisplayedOnCartPage(): bool;

    public function getCartPageWidgetConfiguration(): Configuration;

    public function getCartPageHtmlStyles(): HtmlStyles;

    public function isWidgetDisplayedOnProductCard(): bool;

    public function getProductCardWidgetConfiguration(): Configuration;
}
