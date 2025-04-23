<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Price;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\Price;

interface PriceCalculatorInterface
{
    public function calculatePrice(PriceQuery $query): ?Price;

    public function getCalculationParameters(Currency $currency, ?int $shopId = null): CalculationParameters;
}
