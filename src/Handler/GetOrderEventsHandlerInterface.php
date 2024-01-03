<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\GetOrderEventsCommand;
use izi\prestashop\Handler\Result\OrderEventStream;

interface GetOrderEventsHandlerInterface
{
    public function __invoke(GetOrderEventsCommand $command): OrderEventStream;
}
