<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Order\Request;

final class AddressDetails implements \JsonSerializable
{
    public const BUILDING_NUMBER_PLACEHOLDER = '_';

    /**
     * @var string|null
     */
    private $street;

    /**
     * @var string|null
     */
    private $building;

    /**
     * @var string|null
     */
    private $flat;

    public function __construct(?string $street = null, ?string $building = null, ?string $flat = null)
    {
        $this->street = $street;
        $this->building = $building;
        $this->flat = $flat;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getBuilding(): ?string
    {
        return $this->building;
    }

    public function getFlat(): ?string
    {
        return $this->flat;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
