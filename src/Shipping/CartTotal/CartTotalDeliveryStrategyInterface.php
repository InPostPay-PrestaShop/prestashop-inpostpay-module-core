<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\CartTotal;

use izi\prestashop\Common\Price;

interface CartTotalDeliveryStrategyInterface
{
    /**
     * @return bool true if shipping is available based on the total price of the cart
     */
    public function isShippingAvailableBasedOnTotalPrice(\Carrier $carrier, Price $cartTotal): bool;
}
