<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class Consent implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     */
    private $cmsPageId;

    /**
     * @var string[]
     *
     * @Assert\All({
     *   @Assert\NotBlank(),
     * })
     */
    private $descriptions;

    /**
     * @var ConsentRequirementType|null
     *
     * @Assert\NotNull()
     */
    private $requirementType;

    /**
     * @var \DateTimeImmutable
     */
    private $dateUpdated;

    private $dirty = false;

    /**
     * @param string[] $descriptions consent text by language ID
     */
    public function __construct(string $id = null, int $cmsPageId = null, array $descriptions = [], ConsentRequirementType $requirementType = null, \DateTimeImmutable $dateUpdated = null)
    {
        $this->id = $id ?? (string) Uuid::v4();
        $this->cmsPageId = $cmsPageId;
        $this->descriptions = $descriptions;
        $this->requirementType = $requirementType;
        $this->dateUpdated = $dateUpdated;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCmsPageId(): ?int
    {
        return $this->cmsPageId;
    }

    public function setCmsPageId(?\CMS $cmsPage): self
    {
        $cmsPageId = null === $cmsPage ? null : (int) $cmsPage->id;

        $this->dirty = $this->dirty || $this->cmsPageId !== $cmsPageId;
        $this->cmsPageId = $cmsPageId;

        return $this;
    }

    public function getDescriptions(): array
    {
        return $this->descriptions;
    }

    public function getDescription(int $languageId): string
    {
        return $this->descriptions[$languageId] ?? '';
    }

    /**
     * @param array<int, string> $descriptions consent text by language ID
     */
    public function setDescriptions(array $descriptions): self
    {
        $this->dirty = $this->dirty || $this->descriptions !== $descriptions;
        $this->descriptions = $descriptions;

        return $this;
    }

    public function getRequirementType(): ConsentRequirementType
    {
        return $this->requirementType ?? ConsentRequirementType::Optional();
    }

    public function setRequirementType(?ConsentRequirementType $requirementType): self
    {
        $this->dirty = $this->dirty || $this->requirementType !== $requirementType;
        $this->requirementType = $requirementType;

        return $this;
    }

    public function getDateUpdated(): ?\DateTimeImmutable
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(?\DateTimeImmutable $dateUpdated): self
    {
        if ($this->dirty || null === $this->dateUpdated) {
            $this->dateUpdated = $dateUpdated;
            $this->dirty = false;
        }

        return $this;
    }

    public function getVersion(): string
    {
        if (null === $this->dateUpdated) {
            return '0';
        }

        return (string) $this->dateUpdated->getTimestamp();
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'cmsPageId' => $this->cmsPageId,
            'descriptions' => $this->descriptions,
            'requirementType' => $this->getRequirementType(),
            'dateUpdated' => $this->dateUpdated->format(\DateTime::RFC3339),
        ];
    }
}
