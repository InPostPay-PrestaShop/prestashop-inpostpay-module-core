<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Merchant()
 * @method static self OnlyInApp()
 */
final class PromotionType extends StringEnum
{
    private const MERCHANT = 'MERCHANT';
    private const ONLY_IN_APP = 'ONLY_IN_APP';
}
