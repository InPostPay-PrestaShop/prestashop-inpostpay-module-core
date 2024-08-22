<?php

namespace izi\prestashop\BasketApp\Order\Request;

use DateTimeImmutable;
use izi\prestashop\Common\Order\DeliveryAddress;
use izi\prestashop\Common\PhoneNumber;

final class Delivery implements \JsonSerializable
{
    /**
     * @var \DateTimeImmutable|null
     */
    private $delivery_date;

    /**
     * @var string|null
     */
    private $mail;

    /**
     * @var PhoneNumber|null
     */
    private $phone_number;

    /**
     * @var string|null
     */
    private $delivery_point;

    /**
     * @var DeliveryAddress|null
     */
    private $delivery_address;

    /**
     * @var string|null
     */
    private $courier_note;


    public function __construct(?DateTimeImmutable $delivery_date = null, ?string $mail = null, ?PhoneNumber $phone_number = null, ?string $delivery_point = null, ?DeliveryAddress $delivery_address = null, ?string $courier_note = null)
    {
        $this->delivery_date = $delivery_date;
        $this->mail = $mail;
        $this->phone_number = $phone_number;
        $this->delivery_point = $delivery_point;
        $this->delivery_address = $delivery_address;
        $this->courier_note = $courier_note;
    }

    public function getDeliveryDate(): ?DateTimeImmutable
    {
        return $this->delivery_date;
    }

    public function getEmail(): ?string
    {
        return $this->mail;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phone_number;
    }

    public function getPoint(): ?string
    {
        return $this->delivery_point;
    }

    public function getAddress(): ?DeliveryAddress
    {
        return $this->delivery_address;
    }

    public function getCourierNote(): ?string
    {
        return $this->courier_note;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
