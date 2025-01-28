<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Price;

use izi\prestashop\Common\Price;

interface LowestPriceProviderInterface
{
    public function getPrice(LowestPriceQuery $query): ?Price;
}
