<?php

declare(strict_types=1);

namespace izi\prestashop\Webservice\Model;

if (class_exists(\BaseLinkerOrder::class, false)) {
    class InPostOrder extends \BaseLinkerOrder
    {
        use InPostOrderTrait;
    }
} else {
    class InPostOrder extends \Order
    {
        use InPostOrderTrait;
    }
}
