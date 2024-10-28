<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\MerchantApi\Command\UpdateOrderCommand;
use izi\prestashop\MerchantApi\Model\Order\Response\OrderStatusData;

interface UpdateOrderHandlerInterface
{
    public function __invoke(UpdateOrderCommand $command): OrderStatusData;
}
