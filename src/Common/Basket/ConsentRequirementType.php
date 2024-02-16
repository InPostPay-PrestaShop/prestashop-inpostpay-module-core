<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Optional()
 * @method static self RequiredOnce()
 * @method static self RequiredAlways()
 */
final class ConsentRequirementType extends StringEnum
{
    private const OPTIONAL = 'OPTIONAL';
    private const REQUIRED_ONCE = 'REQUIRED_ONCE';
    private const REQUIRED_ALWAYS = 'REQUIRED_ALWAYS';
}
