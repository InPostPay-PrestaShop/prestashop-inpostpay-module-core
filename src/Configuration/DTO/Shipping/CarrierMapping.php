<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Shipping;

use izi\prestashop\Common\Delivery\ServiceCode;
use Symfony\Component\Validator\Constraints as Assert;

final class CarrierMapping implements \JsonSerializable
{
    /**
     * @var int|null
     */
    private $referenceId;

    /**
     * @var ServiceCode[]
     *
     * @Assert\All(
     *     @Assert\Type(ServiceCode::class),
     * )
     */
    private $serviceCodes;

    /**
     * @param ServiceCode[] $serviceCodes
     */
    public function __construct(?int $referenceId = null, array $serviceCodes = [])
    {
        $this->referenceId = $referenceId;
        $this->serviceCodes = $serviceCodes;
    }

    public function getReferenceId(): ?int
    {
        return $this->referenceId;
    }

    public function setReferenceId(?\Carrier $carrier): self
    {
        $this->referenceId = null === $carrier ? null : (int) $carrier->id_reference;

        return $this;
    }

    /**
     * @return ServiceCode[]
     */
    public function getServiceCodes(): array
    {
        return $this->serviceCodes;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
