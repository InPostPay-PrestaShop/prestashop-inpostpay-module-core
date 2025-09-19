<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Product;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Physical()
 * @method static self Digital()
 */
final class ProductType extends StringEnum
{
    private const PHYSICAL = 'PRODUCT';
    private const DIGITAL = 'DIGITAL';
}
