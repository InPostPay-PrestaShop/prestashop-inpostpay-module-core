<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommand;

interface UpdateGeneralConfigurationHandlerInterface
{
    public function __invoke(UpdateGeneralConfigurationCommand $command);
}
