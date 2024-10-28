<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Order;

final class DeliveryAddress implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
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


    public function __construct(string $address, string $city, string $postal_code, ?string $name = null, ?string $country_code = null)
    {
        $this->name = $name;
        $this->country_code = $country_code;
        $this->address = $address;
        $this->city = $city;
        $this->postal_code = $postal_code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCountryCode(): ?string
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

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
