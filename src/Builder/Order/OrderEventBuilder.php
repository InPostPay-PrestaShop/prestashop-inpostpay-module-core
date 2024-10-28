<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Order;

use izi\prestashop\BasketApp\Order\Request\Delivery;
use izi\prestashop\BasketApp\Order\Request\OrderEvent;
use izi\prestashop\BasketApp\Order\Request\OrderEventData;
use izi\prestashop\Common\Order\MerchantOrderStatus;
use Psr\Clock\ClockInterface;

final class OrderEventBuilder implements OrderEventBuilderInterface
{
    /**
     * @var \Order
     */
    private $order;

    /**
     * @var ClockInterface
     */
    private $clock;

    /**
     * @var OrderStatusDescriptionProvider
     */
    private $statusDescriptionProvider;

    /**
     * @var string|null
     */
    private $eventId;

    /**
     * @var \DateTimeImmutable|null
     */
    private $eventTime;

    /**
     * @var MerchantOrderStatus|null
     */
    private $status;

    /**
     * @var string[]|null
     */
    private $trackingNumbers;

    /**
     * @var Delivery|null
     */
    private $delivery;

    public function __construct(\Order $order, ClockInterface $clock, OrderStatusDescriptionProvider $orderStatusDescriptionProvider)
    {
        $this->order = $order;
        $this->clock = $clock;
        $this->statusDescriptionProvider = $orderStatusDescriptionProvider;
    }

    /**
     * @return static
     */
    public function setEventId(string $eventId): OrderEventBuilderInterface
    {
        $this->eventId = $eventId;

        return $this;
    }

    /**
     * @return static
     */
    public function setEventTime(\DateTimeImmutable $time): OrderEventBuilderInterface
    {
        $this->eventTime = $time;

        return $this;
    }

    /**
     * @return static
     */
    public function setOrderStatus(?MerchantOrderStatus $status): OrderEventBuilderInterface
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return static
     */
    public function setTrackingNumbers(?array $numbers): OrderEventBuilderInterface
    {
        $this->trackingNumbers = $numbers;

        return $this;
    }

    /**
     * @return static
     */
    public function setDeliveryData(?Delivery $delivery): OrderEventBuilderInterface
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function build(): OrderEvent
    {
        $eventData = $this->createEventData();
        $eventTime = $this->getEventTime();
        $eventId = $this->getEventId($eventTime);

        return new OrderEvent($eventId, $eventTime, $eventData);
    }

    private function getEventId(\DateTimeImmutable $eventTime): string
    {
        return $this->eventId ?? (string) $eventTime->getTimestamp();
    }

    private function getEventTime(): \DateTimeImmutable
    {
        return $this->eventTime ?? $this->clock->now();
    }

    private function createEventData(): OrderEventData
    {
        $statusDescription = $this->statusDescriptionProvider->getStatus($this->order);

        return new OrderEventData(
            $this->status,
            $statusDescription,
            $this->trackingNumbers,
            $this->delivery
        );
    }
}
