<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Shipping;

final class TimeOfWeek implements \JsonSerializable
{
    private const TIME_FORMAT = 'H:i:s';

    /**
     * @var WeekDay
     */
    private $weekDay;

    /**
     * @var \DateTimeImmutable
     */
    private $time;

    public function __construct(WeekDay $weekDay = null, \DateTimeImmutable $time = null)
    {
        $this->weekDay = $weekDay ?? WeekDay::Monday();
        $this->time = $time ?? new \DateTimeImmutable('00:00');
    }

    public static function fromDateTime(\DateTimeInterface $dateTime): self
    {
        return new self(
            WeekDay::fromDateTime($dateTime),
            \DateTimeImmutable::createFromFormat(self::TIME_FORMAT, $dateTime->format(self::TIME_FORMAT))
        );
    }

    public function getWeekDay(): WeekDay
    {
        return $this->weekDay;
    }

    public function setWeekDay(WeekDay $weekDay): self
    {
        $this->weekDay = $weekDay;

        return $this;
    }

    public function getTime(): \DateTimeImmutable
    {
        return $this->time;
    }

    public function setTime(\DateTimeImmutable $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'weekDay' => $this->weekDay,
            'time' => $this->time->format(self::TIME_FORMAT),
        ];
    }

    public function compare(self $timeOfWeek): int
    {
        if ($this->weekDay !== $timeOfWeek->weekDay) {
            return $this->weekDay->value <=> $timeOfWeek->weekDay->value;
        }

        return $this->time->format(self::TIME_FORMAT) <=> $timeOfWeek->time->format(self::TIME_FORMAT);
    }
}
