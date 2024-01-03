<?php

declare(strict_types=1);

namespace izi\prestashop\Log;

use Psr\Log\LoggerInterface;

interface LoggerFactoryInterface
{
    public function create(string $name, array $options): LoggerInterface;
}
