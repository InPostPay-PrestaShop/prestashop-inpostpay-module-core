<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Order\Request;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self OrderRejected()
 */
final class OrderStatus extends StringEnum
{
    private const ORDER_REJECTED = 'ORDER_REJECTED';
}
