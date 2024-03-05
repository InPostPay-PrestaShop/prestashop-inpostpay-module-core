<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Shipping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use izi\prestashop\Common\Delivery\ServiceCode;
use Symfony\Component\Validator\Constraints as Assert;

final class ShippingOptions implements \JsonSerializable
{
    /**
     * @var Collection<int|string, CarrierMapping>
     *
     * @Assert\Valid()
     * @Assert\All(
     *     @Assert\Type(CarrierMapping::class),
     * )
     */
    private $carrierMappings;

    /**
     * @var Collection<int|string, ServiceOptions>
     *
     * @Assert\Valid()
     * @Assert\All(
     *      @Assert\Type(ServiceOptions::class),
     *  )
     */
    private $optionalServices;

    /**
     * @param CarrierMapping[] $carrierMappings
     * @param ServiceOptions[] $optionalServices
     */
    public function __construct(array $carrierMappings = [], array $optionalServices = [])
    {
        $this->carrierMappings = new ArrayCollection($carrierMappings);
        $this->optionalServices = new ArrayCollection($optionalServices);
    }

    /**
     * @return CarrierMapping[]
     */
    public function getCarrierMappings(): array
    {
        return $this->carrierMappings->toArray();
    }

    public function getCarrierMapping(ServiceCode ...$serviceCodes): CarrierMapping
    {
        /** @var CarrierMapping $carrierMapping */
        foreach ($this->carrierMappings as $carrierMapping) {
            $mappingServiceCodes = $carrierMapping->getServiceCodes();

            $diff = array_udiff($serviceCodes, $mappingServiceCodes, static function (ServiceCode $code1, ServiceCode $code2): int {
                return $code1->value <=> $code2->value;
            });

            if ([] === $diff && count($mappingServiceCodes) === count($serviceCodes)) {
                return $carrierMapping;
            }
        }

        return new CarrierMapping(null, ...$serviceCodes);
    }

    /**
     * @param CarrierMapping[] $carrierMappings
     */
    public function setCarrierMappings(array $carrierMappings): self
    {
        $this->carrierMappings = new ArrayCollection($carrierMappings);

        return $this;
    }

    /**
     * @return ServiceOptions[]
     */
    public function getOptionalServices(): array
    {
        return $this->optionalServices->toArray();
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
        $this->optionalServices = new ArrayCollection($optionalServices);

        return $this;
    }

    public function __clone()
    {
        $this->carrierMappings = $this->carrierMappings->map(static function (CarrierMapping $mapping) {
            return clone $mapping;
        });

        $this->optionalServices = $this->optionalServices->map(static function (ServiceOptions $options) {
            return clone $options;
        });
    }

    public function jsonSerialize(): array
    {
        return [
            'carrierMappings' => $this->carrierMappings->toArray(),
            'optionalServices' => $this->optionalServices->toArray(),
        ];
    }
}
