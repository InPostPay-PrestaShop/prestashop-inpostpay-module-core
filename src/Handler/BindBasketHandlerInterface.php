<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\BindBasketCommand;
use izi\prestashop\Handler\Result\BasketBindingResult;

interface BindBasketHandlerInterface
{
    public function __invoke(BindBasketCommand $command): BasketBindingResult;
}
