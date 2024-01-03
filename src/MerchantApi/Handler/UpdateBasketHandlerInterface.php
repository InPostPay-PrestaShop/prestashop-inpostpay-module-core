<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\MerchantApi\Command\UpdateBasketCommand;
use izi\prestashop\MerchantApi\Model\Basket\Response\Basket;

interface UpdateBasketHandlerInterface
{
    public function __invoke(UpdateBasketCommand $command): Basket;
}
