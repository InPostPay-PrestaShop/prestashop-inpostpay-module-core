<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping;

use izi\prestashop\Common\Delivery\DeliveryType;

interface DeliveryDateCalculatorInterface
{
    /**
     * @param \DateTimeImmutable|null $orderDate if null, the calculation should use the current time
     */
    public function calculate(\Cart $cart, DeliveryType $deliveryType, ?\DateTimeImmutable $orderDate = null): \DateTimeImmutable;
}
