<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\UpdateOrderAddressDeliveryCommand;

interface UpdateOrderAddressDeliveryHandlerInterface
{
    public function __invoke(UpdateOrderAddressDeliveryCommand $command);
}
