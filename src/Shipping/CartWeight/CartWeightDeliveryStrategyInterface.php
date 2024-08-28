<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\CartWeight;

use izi\prestashop\Common\Weight;

interface CartWeightDeliveryStrategyInterface
{
    /**
     * @return bool true if shipping is available based on the total weight of the cart
     */
    public function isShippingAvailableBasedOnTotalWeight(\Carrier $carrier, Weight $cartWeight): bool;
}
