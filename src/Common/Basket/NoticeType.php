<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Error()
 * @method static self Attention()
 */
final class NoticeType extends StringEnum
{
    private const ERROR = 'ERROR';
    private const ATTENTION = 'ATTENTION';
}
