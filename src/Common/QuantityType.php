<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Decimal()
 * @method static self Integer()
 */
final class QuantityType extends StringEnum
{
    private const DECIMAL = 'DECIMAL';
    private const INTEGER = 'INTEGER';
}
