<?php

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateCartRuleOptionsCommand;

interface UpdateCartRuleOptionsHandlerInterface
{
    public function __invoke(UpdateCartRuleOptionsCommand $command);
}
