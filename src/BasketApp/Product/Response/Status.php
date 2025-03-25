<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Product\Response;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Active()
 * @method static self Inactive()
 */
final class Status extends StringEnum
{
    public const ACTIVE = 'ACTIVE';
    public const INACTIVE = 'INACTIVE';
}
