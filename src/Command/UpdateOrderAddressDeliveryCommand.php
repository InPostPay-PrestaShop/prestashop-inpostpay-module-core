<?php

declare(strict_types=1);

namespace izi\prestashop\Command;

use izi\prestashop\Handler\UpdateOrderAddressDeliveryHandler;

/**
 * @see UpdateOrderAddressDeliveryHandler
 */
final class UpdateOrderAddressDeliveryCommand
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * @var \DateTimeImmutable
     */
    private $eventTime;

    public function __construct(string $orderId, \DateTimeImmutable $eventTime)
    {
        $this->orderId = $orderId;
        $this->eventTime = $eventTime;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getEventTime(): \DateTimeImmutable
    {
        return $this->eventTime;
    }
}
