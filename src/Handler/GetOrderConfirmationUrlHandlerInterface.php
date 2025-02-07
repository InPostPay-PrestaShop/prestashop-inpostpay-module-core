<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\GetOrderConfirmationUrlCommand;

interface GetOrderConfirmationUrlHandlerInterface
{
    public function __invoke(GetOrderConfirmationUrlCommand $command): string;
}
