<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

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
