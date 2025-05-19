<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Order\Request;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Common\PhoneNumber;

final class Delivery implements \JsonSerializable
{
    /**
     * @var DeliveryType
     */
    private $delivery_type;

    /**
     * @var ServiceCode[]
     */
    private $delivery_codes;

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

    /**
     * @param ServiceCode[] $delivery_codes
     */
    public function __construct(DeliveryType $delivery_type, array $delivery_codes = [], ?string $mail = null, ?PhoneNumber $phone_number = null, ?string $delivery_point = null, ?DeliveryAddress $delivery_address = null, ?string $courier_note = null)
    {
        $this->delivery_type = $delivery_type;
        $this->delivery_codes = $delivery_codes;
        $this->mail = $mail;
        $this->phone_number = $phone_number;
        $this->delivery_point = $delivery_point;
        $this->delivery_address = $delivery_address;
        $this->courier_note = $courier_note;
    }

    public function getType(): DeliveryType
    {
        return $this->delivery_type;
    }

    /**
     * @return ServiceCode[]
     */
    public function getOptionalServiceCodes(): array
    {
        return $this->delivery_codes;
    }

    public function getEmail(): ?string
    {
        return $this->mail;
    }

    public function withEmail(?string $email): self
    {
        $delivery = clone $this;
        $delivery->mail = $email;

        return $delivery;
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
