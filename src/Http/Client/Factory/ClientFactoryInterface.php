<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Client\Factory;

use Psr\Http\Client\ClientInterface;

interface ClientFactoryInterface
{
    public function create(): ClientInterface;
}
