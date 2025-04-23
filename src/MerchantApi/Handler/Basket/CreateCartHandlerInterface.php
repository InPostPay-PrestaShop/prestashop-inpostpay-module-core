<?php

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Entities\Cart;
use izi\prestashop\MerchantApi\Command\Basket\CreateCartCommand;

interface CreateCartHandlerInterface
{
    public function __invoke(CreateCartCommand $command): Cart;
}
