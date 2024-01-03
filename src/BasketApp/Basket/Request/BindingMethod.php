<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\Request;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Phone()
 * @method static self DeepLink()
 */
final class BindingMethod extends StringEnum
{
    private const PHONE = 'PHONE';
    private const DEEP_LINK = 'DEEP_LINK';
}
