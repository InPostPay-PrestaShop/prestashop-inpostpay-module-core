<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

final class Hour
{
    public const MIN_HOUR = 1;

    public const MAX_HOUR = 24;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $hour;

    public function __construct(int $id, string $hour)
    {
        $this->id = $id;
        $this->hour = $hour;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getHour(): string
    {
        return $this->hour;
    }
}
