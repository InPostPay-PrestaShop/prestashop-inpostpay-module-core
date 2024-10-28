<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\CartWeight;

use izi\prestashop\Common\Weight;

final class GenericStrategy implements CartWeightDeliveryStrategyInterface
{
    public function isShippingAvailableBasedOnTotalWeight(\Carrier $carrier, Weight $cartWeight): bool
    {
        return $this->checkMaxWeight($carrier, $cartWeight) && (!$carrier->range_behavior || (int) $carrier->shipping_method !== \Carrier::SHIPPING_METHOD_WEIGHT);
    }

    private function checkMaxWeight(\Carrier $carrier, Weight $cartWeight): bool
    {
        $carrierMaxWeight = new Weight((float) $carrier->max_weight);

        return $carrierMaxWeight->equals(new Weight(0.)) || $carrierMaxWeight->greaterThan($cartWeight);
    }
}
