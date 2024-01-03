<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\View\Widget\Configuration;

interface WidgetConfigurationInterface
{
    public function isVisibleToEveryone(): bool;

    public function getCheckoutConfiguration(): Configuration;

    public function isDisplayedOnCartPage(): bool;

    public function getCartPageConfiguration(): Configuration;

    public function getCartPageHtmlStyles(): iterable;

    public function isDisplayedOnProductCard(): bool;

    public function getProductCardConfiguration(): Configuration;
}
