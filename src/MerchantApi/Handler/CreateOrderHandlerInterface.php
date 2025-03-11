<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\MerchantApi\Command\CreateOrderCommand;
use izi\prestashop\MerchantApi\Model\Order\Response\Order;

interface CreateOrderHandlerInterface
{
    /**
     * @return Order created order data
     */
    public function __invoke(CreateOrderCommand $command): Order;
}
