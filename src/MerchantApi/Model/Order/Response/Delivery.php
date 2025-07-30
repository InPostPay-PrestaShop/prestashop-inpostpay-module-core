<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Order\Response;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\OptionalService;
use izi\prestashop\Common\Order\DeliveryAddress;
use izi\prestashop\Common\PhoneNumber;
use izi\prestashop\Common\Price;

final class Delivery implements \JsonSerializable
{
    /**
     * @var DeliveryType
     */
    private $delivery_type;

    /**
     * @var \DateTimeImmutable
     */
    private $delivery_date;

    /**
     * @var OptionalService[]
     */
    private $delivery_options;

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
     * @var Price
     */
    private $delivery_price;

    /**
     * @var string|null
     */
    private $courier_note;

    /**
     * @var string|null
     */
    private $digital_delivery_email;

    /**
     * @param OptionalService[] $delivery_options
     */
    public function __construct(DeliveryType $delivery_type, \DateTimeImmutable $delivery_date, Price $delivery_price, array $delivery_options = [], ?string $mail = null, ?PhoneNumber $phone_number = null, ?string $delivery_point = null, ?DeliveryAddress $delivery_address = null, ?string $courier_note = null, ?string $digital_delivery_email = null)
    {
        $this->delivery_type = $delivery_type;
        $this->delivery_date = $delivery_date;
        $this->delivery_options = $delivery_options;
        $this->mail = $mail;
        $this->phone_number = $phone_number;
        $this->delivery_point = $delivery_point;
        $this->delivery_address = $delivery_address;
        $this->delivery_price = $delivery_price;
        $this->courier_note = $courier_note;
        $this->digital_delivery_email = $digital_delivery_email;
    }

    public function getType(): DeliveryType
    {
        return $this->delivery_type;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->delivery_date;
    }

    /**
     * @return OptionalService[]
     */
    public function getOptionalServices(): array
    {
        return $this->delivery_options;
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

    public function getPrice(): Price
    {
        return $this->delivery_price;
    }

    public function getCourierNote(): ?string
    {
        return $this->courier_note;
    }

    public function getDigitalDeliveryEmail(): ?string
    {
        return $this->digital_delivery_email;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
