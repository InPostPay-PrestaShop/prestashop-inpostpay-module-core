<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\MessageHandler;

use izi\prestashop\HotProduct\HotProduct;
use izi\prestashop\HotProduct\Message\ImportHotProductCommand;

interface ImportHotProductHandlerInterface
{
    public function __invoke(ImportHotProductCommand $command): HotProduct;
}
