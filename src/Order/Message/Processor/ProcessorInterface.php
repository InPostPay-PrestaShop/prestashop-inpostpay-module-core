<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message\Processor;

use izi\prestashop\Order\Message\Message;

interface ProcessorInterface
{
    public function __invoke(Message $message): Message;
}
