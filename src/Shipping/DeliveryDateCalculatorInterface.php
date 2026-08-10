<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping;

use izi\prestashop\Common\Delivery\DeliveryType;

interface DeliveryDateCalculatorInterface
{
    public function calculate(\Cart $cart, DeliveryType $deliveryType): \DateTimeImmutable;
}
