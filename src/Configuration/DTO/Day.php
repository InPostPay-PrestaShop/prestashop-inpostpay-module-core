<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

final class Day
{
    public const MIN_DAY = 0;
    public const MAX_DAY = 7;
    public const MONDAY = 0;
    public const TUESDAY = 1;
    public const WEDNESDAY = 2;
    public const THURSDAY = 3;
    public const FRIDAY = 4;
    public const SATURDAY = 5;
    public const SUNDAY = 6;

    public const DAYS = [
        self::MONDAY,
        self::TUESDAY,
        self::WEDNESDAY,
        self::THURSDAY,
        self::FRIDAY,
        self::SATURDAY,
        self::SUNDAY,
    ];

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDayName(): string
    {
        return $this->name;
    }
}
