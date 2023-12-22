<?php

namespace izi\prestashop\Widget;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Rounded()
 * @method static self Round()
 */
final class FrameStyle extends StringEnum
{
    private const ROUNDED = 'rounded';
    private const ROUND = 'round';
}
