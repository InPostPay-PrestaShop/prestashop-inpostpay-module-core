<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Order;

interface OrderEventBuilderFactoryInterface
{
    public function create(int $orderId): OrderEventBuilderInterface;
}
