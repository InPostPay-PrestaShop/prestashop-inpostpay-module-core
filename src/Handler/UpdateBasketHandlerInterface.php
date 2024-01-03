<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\UpdateBasketCommand;

interface UpdateBasketHandlerInterface
{
    public function __invoke(UpdateBasketCommand $command);
}
