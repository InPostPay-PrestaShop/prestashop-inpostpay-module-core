<?php

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\MerchantApi\Command\Basket\AddProductToCartCommand;

interface AddProductToCartHandlerInterface
{
    public function __invoke(AddProductToCartCommand $command);
}
