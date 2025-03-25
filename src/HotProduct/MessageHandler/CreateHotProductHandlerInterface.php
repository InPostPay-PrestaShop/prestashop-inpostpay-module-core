<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\MessageHandler;

use izi\prestashop\HotProduct\HotProduct;
use izi\prestashop\HotProduct\Message\CreateHotProductCommand;

interface CreateHotProductHandlerInterface
{
    public function __invoke(CreateHotProductCommand $command): HotProduct;
}
