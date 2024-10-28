<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\ProductDimensions;

use izi\prestashop\Common\Dimensions;

final class GenericStrategy implements ProductDimensionsDeliveryStrategyInterface
{
    public function isShippingAvailableBasedOnProductDimensions(\Carrier $carrier, Dimensions $productDimension): bool
    {
        $carrierMaxDimensions = new Dimensions((float) $carrier->max_width, (float) $carrier->max_height, (float) $carrier->max_depth);

        if ($carrierMaxDimensions->getWidth() === 0. && $carrierMaxDimensions->getHeight() === 0. && $carrierMaxDimensions->getDepth() === 0.) {
            return true;
        }

        return $this->checkMaxDimensions($carrierMaxDimensions, $productDimension);
    }

    private function checkMaxDimensions(Dimensions $carrierMaxDimensions, Dimensions $productDimension): bool
    {
        return $carrierMaxDimensions->getWidth() >= $productDimension->getWidth() &&
            $carrierMaxDimensions->getHeight() >= $productDimension->getHeight() &&
            $carrierMaxDimensions->getDepth() >= $productDimension->getDepth();
    }
}
