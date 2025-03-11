<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\MerchantApi\Command\GetOrderCommand;
use izi\prestashop\MerchantApi\Model\Order\Response\Order;

interface GetOrderHandlerInterface
{
    public function __invoke(GetOrderCommand $command): Order;
}
