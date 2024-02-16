<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\Factory;

use izi\prestashop\Configuration\DTO\Hour;

final class HourFactory implements HourFactoryInterface
{
    public function create(int $id): Hour
    {
        if ($id < Hour::MIN_HOUR || $id >= Hour::MAX_HOUR) {
            throw new \InvalidArgumentException('Invalid hour id provided.');
        }

        return new Hour($id, $this->getHourFormatted($id));
    }

    private function getHourFormatted(int $hour): string
    {
        return $hour . ':00';
    }
}
