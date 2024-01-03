<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\UnbindBasketCommand;

interface UnbindBasketHandlerInterface
{
    public function __invoke(UnbindBasketCommand $command);
}
