<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\Factory;

use izi\prestashop\Configuration\DTO\Day;

interface DayFactoryInterface
{
    public function create(int $id): Day;
}
