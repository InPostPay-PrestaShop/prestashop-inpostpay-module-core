<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\UpdateOrderStatusCommand;

interface UpdateOrderStatusHandlerInterface
{
    public function __invoke(UpdateOrderStatusCommand $command);
}
