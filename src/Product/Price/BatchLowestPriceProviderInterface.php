<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Price;

use Symfony\Contracts\Service\ResetInterface;

interface BatchLowestPriceProviderInterface extends LowestPriceProviderInterface, ResetInterface
{
    /**
     * Prepares and stores prices to be later retrieved by {@see self::getPrice()}.
     */
    public function preparePrices(LowestPriceQuery ...$queries);
}
