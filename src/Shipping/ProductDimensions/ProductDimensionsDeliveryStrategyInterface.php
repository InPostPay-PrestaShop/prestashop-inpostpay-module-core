<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\ProductDimensions;

use izi\prestashop\Common\Dimensions;

interface ProductDimensionsDeliveryStrategyInterface
{
    /**
     * @return bool true if shipping is available based on the product dimensions
     */
    public function isShippingAvailableBasedOnProductDimensions(\Carrier $carrier, Dimensions $productDimension): bool;
}
