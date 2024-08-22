<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Order\Request;

use izi\prestashop\Common\Order\MerchantOrderStatusData;
use izi\prestashop\Common\PhoneNumber;

final class OrderEvent implements \JsonSerializable
{
    /**
     * @var string
     */
    private $event_id;

    /**
     * @var \DateTimeImmutable
     */
    private $event_data_time;

    /**
     * @var PhoneNumber|null
     */
    private $phone_number;

    /**
     * @var MerchantOrderStatusData
     */
    private $event_data;

    /**
     * @var string|null
     */
    private $customer_order_id;

    /**
     * @var Delivery|null
     */
    private $delivery;

    public function __construct(string $event_id, \DateTimeImmutable $event_data_time, MerchantOrderStatusData $event_data, ?PhoneNumber $phone_number = null, ?string $customer_order_id = null, ?Delivery $delivery = null)
    {
        $this->event_id = $event_id;
        $this->event_data_time = $event_data_time;
        $this->phone_number = $phone_number;
        $this->event_data = $event_data;
        $this->customer_order_id = $customer_order_id;
        $this->delivery = $delivery;
    }

    public function getId(): string
    {
        return $this->event_id;
    }

    public function getDateTime(): \DateTimeImmutable
    {
        return $this->event_data_time;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phone_number;
    }

    public function getData(): MerchantOrderStatusData
    {
        return $this->event_data;
    }

    public function getCustomerOrderId(): ?string
    {
        return $this->customer_order_id;
    }

    public function getDelivery(): ?Delivery
    {
        return $this->delivery;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
