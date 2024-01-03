<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\UpdateOrderTrackingNumbersCommand;

interface UpdateOrderTrackingNumbersHandlerInterface
{
    public function __invoke(UpdateOrderTrackingNumbersCommand $command);
}
