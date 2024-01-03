<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\MerchantApi\Command\ConfirmBasketBindingCommand;
use izi\prestashop\MerchantApi\Model\Basket\Response\Basket;

interface ConfirmBasketBindingHandlerInterface
{
    public function __invoke(ConfirmBasketBindingCommand $command): Basket;
}
