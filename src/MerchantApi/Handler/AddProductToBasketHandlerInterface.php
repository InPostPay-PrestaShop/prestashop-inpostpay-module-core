<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\MerchantApi\Command\AddProductToBasketCommand;
use izi\prestashop\MerchantApi\Model\Basket\Response\IdentifiableBasket;

interface AddProductToBasketHandlerInterface
{
    public function __invoke(AddProductToBasketCommand $command): IdentifiableBasket;
}
