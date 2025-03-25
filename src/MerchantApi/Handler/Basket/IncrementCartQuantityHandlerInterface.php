<?php

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\MerchantApi\Command\Basket\IncrementCartQuantityCommand;

interface IncrementCartQuantityHandlerInterface
{
    /**
     * @return int new quantity
     */
    public function __invoke(IncrementCartQuantityCommand $command): int;
}
