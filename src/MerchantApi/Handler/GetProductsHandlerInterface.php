<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\MerchantApi\Command\GetProductsCommand;
use izi\prestashop\MerchantApi\Model\Product\Response\Products;

interface GetProductsHandlerInterface
{
    public function __invoke(GetProductsCommand $command): Products;
}
