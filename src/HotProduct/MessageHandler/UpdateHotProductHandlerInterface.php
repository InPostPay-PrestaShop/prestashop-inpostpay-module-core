<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\MessageHandler;

use izi\prestashop\BasketApp\Product\Response\Status;
use izi\prestashop\HotProduct\Message\UpdateHotProductCommand;

interface UpdateHotProductHandlerInterface
{
    public function __invoke(UpdateHotProductCommand $command): Status;
}
