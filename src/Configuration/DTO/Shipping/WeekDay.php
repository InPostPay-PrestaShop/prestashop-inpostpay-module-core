<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Shipping;

use izi\prestashop\Enum\IntEnum;

/**
 * @method static self Monday()
 * @method static self Tuesday()
 * @method static self Wednesday()
 * @method static self Thursday()
 * @method static self Friday()
 * @method static self Saturday()
 * @method static self Sunday()
 */
final class WeekDay extends IntEnum
{
    private const MONDAY = 1;
    private const TUESDAY = 2;
    private const WEDNESDAY = 3;
    private const THURSDAY = 4;
    private const FRIDAY = 5;
    private const SATURDAY = 6;
    private const SUNDAY = 7;

    public static function fromDateTime(\DateTimeInterface $dateTime): self
    {
        return self::from((int) $dateTime->format('N'));
    }
}
