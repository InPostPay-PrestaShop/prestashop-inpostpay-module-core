<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

final class Consent implements \JsonSerializable
{
    /**
     * @var string
     */
    private $consent_id;

    /**
     * @var string
     */
    private $consent_link;

    /**
     * @var string
     */
    private $consent_description;

    /**
     * @var string
     */
    private $consent_version;

    /**
     * @var ConsentRequirementType
     */
    private $requirement_type;

    public function __construct(string $consent_id, string $consent_link, string $consent_description, string $consent_version, ConsentRequirementType $requirement_type)
    {
        $this->consent_id = $consent_id;
        $this->consent_link = $consent_link;
        $this->consent_description = $consent_description;
        $this->consent_version = $consent_version;
        $this->requirement_type = $requirement_type;
    }

    public function getId(): string
    {
        return $this->consent_id;
    }

    public function getLink(): string
    {
        return $this->consent_link;
    }

    public function getDescription(): string
    {
        return $this->consent_description;
    }

    public function getVersion(): string
    {
        return $this->consent_version;
    }

    public function getRequirementType(): ConsentRequirementType
    {
        return $this->requirement_type;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
