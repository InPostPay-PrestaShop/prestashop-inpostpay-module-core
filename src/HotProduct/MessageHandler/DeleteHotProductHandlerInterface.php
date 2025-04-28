<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\MessageHandler;

use izi\prestashop\HotProduct\Message\DeleteHotProductCommand;

interface DeleteHotProductHandlerInterface
{
    public function __invoke(DeleteHotProductCommand $command);
}
