<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\MessageHandler;

use izi\prestashop\HotProduct\Message\DeleteRemoteProductCommand;

interface DeleteRemoteProductHandlerInterface
{
    public function __invoke(DeleteRemoteProductCommand $command);
}
