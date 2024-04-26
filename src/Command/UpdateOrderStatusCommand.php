<?php

declare(strict_types=1);

namespace izi\prestashop\Command;

use izi\prestashop\Common\Order\MerchantOrderStatus;
use izi\prestashop\Handler\UpdateOrderStatusHandler;

/**
 * @see UpdateOrderStatusHandler
 */
final class UpdateOrderStatusCommand
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * @var \DateTimeImmutable
     */
    private $eventTime;

    /**
     * @var MerchantOrderStatus|null
     */
    private $status;

    public function __construct(string $orderId, \DateTimeImmutable $eventTime, ?MerchantOrderStatus $status = null)
    {
        $this->orderId = $orderId;
        $this->eventTime = $eventTime;
        $this->status = $status;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getEventTime(): \DateTimeImmutable
    {
        return $this->eventTime;
    }

    public function getStatus(): ?MerchantOrderStatus
    {
        return $this->status;
    }
}
