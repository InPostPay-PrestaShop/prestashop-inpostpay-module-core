<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Order;

use izi\prestashop\MerchantApi\Model\Order\Request\AddressDetails;

final class DeliveryAddress implements \JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $country_code;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $postal_code;

    /**
     * @var AddressDetails|null
     */
    private $address_details;

    public function __construct(string $name, string $country_code, string $address, string $city, string $postal_code, AddressDetails $address_details = null)
    {
        $this->name = $name;
        $this->country_code = $country_code;
        $this->address = $address;
        $this->city = $city;
        $this->postal_code = $postal_code;
        $this->address_details = $address_details;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCountryCode(): string
    {
        return $this->country_code;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPostalCode(): string
    {
        return $this->postal_code;
    }

    public function getAddressDetails(): ?AddressDetails
    {
        return $this->address_details;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
