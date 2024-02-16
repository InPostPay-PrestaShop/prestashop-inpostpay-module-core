<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\CheckStatusCommand;

interface CheckStatusHandlerInterface
{
    public function __invoke(CheckStatusCommand $command): ModuleStatus;
}
