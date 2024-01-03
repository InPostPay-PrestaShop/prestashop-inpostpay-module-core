<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Order;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self OrderCompleted()
 * @method static self OrderRejected()
 */
final class MerchantOrderStatus extends StringEnum
{
    private const ORDER_COMPLETED = 'ORDER_COMPLETED';
    private const ORDER_REJECTED = 'ORDER_REJECTED';
}
