<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Request;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Success()
 * @method static self Reject()
 */
final class BindingStatus extends StringEnum
{
    private const SUCCESS = 'SUCCESS';
    private const REJECT = 'REJECT';
}
