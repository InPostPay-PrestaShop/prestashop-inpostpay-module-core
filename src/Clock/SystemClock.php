<?php

declare(strict_types=1);

namespace izi\prestashop\Clock;

use Psr\Clock\ClockInterface;

final class SystemClock implements ClockInterface
{
    private $timezone;

    public function __construct(\DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }

    public static function fromSystemTimezone(): self
    {
        return new self(new \DateTimeZone(date_default_timezone_get()));
    }

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->timezone);
    }
}
