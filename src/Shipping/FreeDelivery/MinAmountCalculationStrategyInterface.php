<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\FreeDelivery;

interface MinAmountCalculationStrategyInterface
{
    /**
     * @return float|null gross amount required for free delivery or null if not applicable
     */
    public function getMinAmount(\Cart $cart, \Carrier $carrier): ?float;
}
