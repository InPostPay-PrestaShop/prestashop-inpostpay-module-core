<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateAdvancedConfigurationCommand;

interface UpdateAdvancedConfigurationHandlerInterface
{
    public function __invoke(UpdateAdvancedConfigurationCommand $command);
}
