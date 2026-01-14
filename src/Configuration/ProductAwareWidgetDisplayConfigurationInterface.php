<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;

interface ProductAwareWidgetDisplayConfigurationInterface extends WidgetDisplayConfigurationInterface
{
    public function isDisplayed(?ProductLazyArray $product = null): bool;
}
