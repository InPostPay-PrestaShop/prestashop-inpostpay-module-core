<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Pln()
 */
final class Currency extends StringEnum
{
    private const PLN = 'PLN';

    public static function getDefault(): self
    {
        return self::Pln();
    }
}
