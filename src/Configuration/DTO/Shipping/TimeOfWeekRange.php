<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Shipping;

final class TimeOfWeekRange implements \JsonSerializable
{
    /**
     * @var TimeOfWeek
     */
    private $start;

    /**
     * @var TimeOfWeek
     */
    private $end;

    public function __construct(?TimeOfWeek $start = null, ?TimeOfWeek $end = null)
    {
        $this->start = $start ?? new TimeOfWeek();
        $this->end = $end ?? new TimeOfWeek();
    }

    public function getStart(): TimeOfWeek
    {
        return $this->start;
    }

    public function setStart(TimeOfWeek $start): TimeOfWeekRange
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): TimeOfWeek
    {
        return $this->end;
    }

    public function setEnd(TimeOfWeek $end): TimeOfWeekRange
    {
        $this->end = $end;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function contains(\DateTimeInterface $dateTime): bool
    {
        $timeOfWeek = TimeOfWeek::fromDateTime($dateTime);

        return 0 > $this->start->compare($this->end)
            ? 0 >= $this->start->compare($timeOfWeek) && 0 < $this->end->compare($timeOfWeek)
            : 0 <= $this->start->compare($timeOfWeek) || 0 > $this->end->compare($timeOfWeek);
    }

    public function __clone()
    {
        $this->start = clone $this->start;
        $this->end = clone $this->end;
    }
}
