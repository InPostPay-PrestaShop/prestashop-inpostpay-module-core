<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Shipping;

use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Enum\Enum;
use izi\prestashop\Shipping\DeliveryDateCalculator;
use Symfony\Component\Validator\Constraints as Assert;

final class ShippingOptions implements \JsonSerializable
{
    /**
     * @var CarrierMapping[]
     *
     * @Assert\Valid()
     * @Assert\All(
     *     @Assert\Type(CarrierMapping::class),
     * )
     */
    private $carrierMappings;

    /**
     * @var ServiceOptions[]
     *
     * @Assert\Valid()
     * @Assert\All(
     *     @Assert\Type(ServiceOptions::class),
     * )
     */
    private $optionalServices;

    /**
     * @var int|null
     *
     * @Assert\GreaterThan(0)
     * @Assert\LessThanOrEqual(1440)
     */
    private $estimatedDeliveryTime = DeliveryDateCalculator::DEFAULT_DELIVERY_TIME_HOURS;

    /**
     * @param CarrierMapping[] $carrierMappings
     * @param ServiceOptions[] $optionalServices
     */
    public function __construct(array $carrierMappings = [], array $optionalServices = [])
    {
        $this->carrierMappings = $carrierMappings;
        $this->optionalServices = $optionalServices;
    }

    /**
     * @return CarrierMapping[]
     */
    public function getCarrierMappings(): array
    {
        return $this->carrierMappings;
    }

    public function getCarrierMapping(ServiceCode ...$serviceCodes): CarrierMapping
    {
        $serviceCodes = array_filter($serviceCodes, static function (ServiceCode $serviceCode) {
            return $serviceCode === ServiceCode::Cod() || $serviceCode === ServiceCode::Pww();
        });

        foreach ($this->carrierMappings as $carrierMapping) {
            $mappingServiceCodes = $carrierMapping->getServiceCodes();

            $diff = array_udiff($serviceCodes, $mappingServiceCodes, [Enum::class, 'compareValues']);

            if ([] === $diff && \count($mappingServiceCodes) === \count($serviceCodes)) {
                return $carrierMapping;
            }
        }

        return new CarrierMapping(null, $serviceCodes);
    }

    /**
     * @param CarrierMapping[] $carrierMappings
     */
    public function setCarrierMappings(array $carrierMappings): self
    {
        $this->carrierMappings = $carrierMappings;

        return $this;
    }

    /**
     * @return ServiceOptions[]
     */
    public function getOptionalServices(): array
    {
        return $this->optionalServices;
    }

    public function getServiceOptions(ServiceCode $serviceCode): ?ServiceOptions
    {
        foreach ($this->optionalServices as $serviceOptions) {
            if ($serviceCode === $serviceOptions->getServiceCode()) {
                return $serviceOptions;
            }
        }

        return null;
    }

    /**
     * @param ServiceOptions[] $optionalServices
     */
    public function setOptionalServices(array $optionalServices): self
    {
        $this->optionalServices = $optionalServices;

        return $this;
    }

    /**
     * @return int|null time in hours
     */
    public function getEstimatedDeliveryTime(): ?int
    {
        return $this->estimatedDeliveryTime;
    }

    public function setEstimatedDeliveryTime(?int $hours): self
    {
        $this->estimatedDeliveryTime = $hours;

        return $this;
    }

    public function __clone()
    {
        $this->carrierMappings = array_map(static function (CarrierMapping $mapping) {
            return clone $mapping;
        }, $this->carrierMappings);

        $this->optionalServices = array_map(static function (ServiceOptions $options) {
            return clone $options;
        }, $this->optionalServices);
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
