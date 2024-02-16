<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\Factory;

use izi\prestashop\Configuration\DTO\Hour;

interface HourFactoryInterface
{
    public function create(int $id): Hour;
}
