<?php

namespace izi\prestashop\Widget;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Primary()
 * @method static self Secondary()
 */
final class Variant extends StringEnum
{
    private const PRIMARY = 'primary';
    private const SECONDARY = 'secondary';
}
