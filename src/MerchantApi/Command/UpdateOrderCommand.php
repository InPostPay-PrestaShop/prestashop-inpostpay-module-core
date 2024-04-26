<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command;

use izi\prestashop\MerchantApi\Handler\UpdateOrderHandler;
use izi\prestashop\MerchantApi\Model\Order\Request\OrderEvent;

/**
 * @see UpdateOrderHandler
 */
final class UpdateOrderCommand
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * @var OrderEvent
     */
    private $event;

    public function __construct(string $orderId, OrderEvent $event)
    {
        $this->orderId = $orderId;
        $this->event = $event;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getEvent(): OrderEvent
    {
        return $this->event;
    }
}
