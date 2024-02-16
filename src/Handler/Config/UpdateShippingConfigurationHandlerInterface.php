<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateShippingConfigurationCommand;

interface UpdateShippingConfigurationHandlerInterface
{
    public function __invoke(UpdateShippingConfigurationCommand $command);
}
