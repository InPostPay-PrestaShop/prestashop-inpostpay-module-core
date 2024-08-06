<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\FreeDelivery;

final class NullStrategy implements MinAmountCalculationStrategyInterface
{
    public function getMinAmount(\Cart $cart, \Carrier $carrier): ?float
    {
        return null;
    }
}
