<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\ProductDimensions;

use izi\prestashop\Common\Dimensions;

final class ProductDimensionsStrategy implements ProductDimensionsDeliveryStrategyInterface
{
    /**
     * @var ProductDimensionsDeliveryStrategyInterface
     */
    private $genericStrategy;

    public function __construct(ProductDimensionsDeliveryStrategyInterface $genericStrategy)
    {
        $this->genericStrategy = $genericStrategy;
    }

    public function isShippingAvailableBasedOnProductDimensions(\Carrier $carrier, Dimensions $productDimension): bool
    {
        if ($this->genericStrategy->isShippingAvailableBasedOnProductDimensions($carrier, $productDimension)) {
            return true;
        }

        $carrierMaxDimensions = new Dimensions((float) $carrier->max_width, (float) $carrier->max_height, (float) $carrier->max_depth);

        return $this->checkMaxDimensions($carrierMaxDimensions, $productDimension);
    }

    private function checkMaxDimensions(Dimensions $carrierMaxDimensions, Dimensions $productDimension): bool
    {
        return $carrierMaxDimensions->getWidth() >= $productDimension->getWidth() &&
            $carrierMaxDimensions->getHeight() >= $productDimension->getHeight() &&
            $carrierMaxDimensions->getDepth() >= $productDimension->getDepth();
    }
}
