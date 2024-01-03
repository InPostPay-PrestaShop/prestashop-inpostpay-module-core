<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\MerchantApi\Command\GetBasketCommand;
use izi\prestashop\MerchantApi\Model\Basket\Response\Basket;

interface GetBasketHandlerInterface
{
    public function __invoke(GetBasketCommand $command): Basket;
}
