<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;

interface ProductAwareWidgetDisplayConfigurationInterface extends WidgetDisplayConfigurationInterface
{
    /**
     * @param array|ProductLazyArray $product product presentation data
     */
    public function isDisplayed($product = null): bool;
}
