<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\CartTotal;

use izi\prestashop\Common\Price;

final class GenericStrategy implements CartTotalDeliveryStrategyInterface
{
    public function isShippingAvailableBasedOnTotalPrice(\Carrier $carrier, Price $cartTotal): bool
    {
        return !$carrier->range_behavior || (int) $carrier->shipping_method !== \Carrier::SHIPPING_METHOD_PRICE;
    }
}
