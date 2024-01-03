<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Order;

use izi\prestashop\BasketApp\Exception\CannotChangeOrderStatusException;
use izi\prestashop\BasketApp\Exception\OrderNotFoundException;
use izi\prestashop\BasketApp\Order\Request\OrderEvent;

interface OrdersApiClientInterface
{
    /**
     * @throws OrderNotFoundException
     * @throws CannotChangeOrderStatusException
     */
    public function updateOrder(string $orderId, OrderEvent $event): void;
}
