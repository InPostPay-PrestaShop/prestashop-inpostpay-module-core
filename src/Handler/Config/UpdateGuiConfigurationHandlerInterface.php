<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateGuiConfigurationCommand;

interface UpdateGuiConfigurationHandlerInterface
{
    public function __invoke(UpdateGuiConfigurationCommand $command);
}
