<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Shipping;

use izi\prestashop\Common\Delivery\ServiceCode;
use Symfony\Component\Validator\Constraints as Assert;

final class ServiceOptions implements \JsonSerializable
{
    /**
     * @var ServiceCode
     */
    private $serviceCode;

    /**
     * @var float|null
     *
     * @Assert\GreaterThanOrEqual(0)
     */
    private $additionalCost;

    /**
     * @var TimeOfWeekRange|null
     */
    private $availabilityRange;

    public function __construct(ServiceCode $serviceCode, float $additionalCost = null, TimeOfWeekRange $availabilityRange = null)
    {
        $this->serviceCode = $serviceCode;
        $this->additionalCost = $additionalCost;
        $this->availabilityRange = $availabilityRange;
    }

    public function getServiceCode(): ServiceCode
    {
        return $this->serviceCode;
    }

    public function getAdditionalCost(): ?float
    {
        return $this->additionalCost;
    }

    public function setAdditionalCost(?float $additionalCost): ServiceOptions
    {
        $this->additionalCost = $additionalCost;

        return $this;
    }

    public function getAvailabilityRange(): ?TimeOfWeekRange
    {
        return $this->availabilityRange;
    }

    public function setAvailabilityRange(?TimeOfWeekRange $availabilityRange): ServiceOptions
    {
        $this->availabilityRange = $availabilityRange;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function __clone()
    {
        $this->availabilityRange = null === $this->availabilityRange ? null : clone $this->availabilityRange;
    }
}
