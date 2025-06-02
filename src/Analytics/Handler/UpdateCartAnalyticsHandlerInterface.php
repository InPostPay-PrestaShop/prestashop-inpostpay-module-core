<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Handler;

use izi\prestashop\Analytics\Command\UpdateCartAnalyticsCommand;

interface UpdateCartAnalyticsHandlerInterface
{
    public function __invoke(UpdateCartAnalyticsCommand $command);
}
