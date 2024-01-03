<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Delivery;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Cod() cash on delivery option
 * @method static self Pww() weekend delivery option
 */
final class ServiceCode extends StringEnum
{
    private const COD = 'COD';
    private const PWW = 'PWW';
}
