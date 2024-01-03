<?php

declare(strict_types=1);

namespace izi\prestashop\Log\Handler;

use Monolog\Handler\HandlerInterface;

interface HandlerFactoryInterface
{
    public function create(array $options): HandlerInterface;

    public function supports(string $type): bool;
}
