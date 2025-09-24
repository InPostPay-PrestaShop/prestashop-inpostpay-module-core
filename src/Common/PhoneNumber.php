<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

final class PhoneNumber implements \JsonSerializable
{
    /**
     * @var string
     */
    private $country_prefix;

    /**
     * @var string
     */
    private $phone;

    public function __construct(string $country_prefix, string $phone)
    {
        $this->country_prefix = $country_prefix;
        $this->phone = $phone;
    }

    public function getCountryPrefix(): string
    {
        return $this->country_prefix;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
