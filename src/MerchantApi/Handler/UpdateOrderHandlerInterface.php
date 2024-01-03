<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\Common\Order\MerchantOrderStatusData;
use izi\prestashop\MerchantApi\Command\UpdateOrderCommand;

interface UpdateOrderHandlerInterface
{
    public function __invoke(UpdateOrderCommand $command): MerchantOrderStatusData;
}
