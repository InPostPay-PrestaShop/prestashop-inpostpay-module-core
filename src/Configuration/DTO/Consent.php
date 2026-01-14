<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Validator\Consent\DescriptionUsesIdPlaceholders;
use izi\prestashop\Validator\Consent\UniqueIdentifiers;
use izi\prestashop\Validator\NotBlankInDefaultLanguage;
use izi\prestashop\Validator\Unique;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @UniqueIdentifiers(groups = {Consent::VALIDATION_GROUP})
 *
 * @DescriptionUsesIdPlaceholders(groups = {Consent::VALIDATION_GROUP})
 */
final class Consent implements \JsonSerializable, DenormalizableInterface
{
    public const ADDITIONAL_LINKS_COUNT_MAX = 3;
    public const VALIDATION_GROUP = 'consent';

    /**
     * @var ConsentLink
     *
     * @Assert\Valid
     */
    private $link;

    /**
     * @var string[]
     *
     * @NotBlankInDefaultLanguage
     */
    private $descriptions;

    /**
     * @var ConsentRequirementType|null
     *
     * @Assert\NotNull
     */
    private $requirementType;

    /**
     * @var ConsentLink[]
     *
     * @Assert\Count(max = Consent::ADDITIONAL_LINKS_COUNT_MAX)
     * @Assert\Valid
     *
     * @Unique(normalizer = {ConsentLink::class, "normalize"})
     */
    private $additionalLinks;

    /**
     * @var \DateTimeImmutable
     */
    private $dateUpdated;

    /**
     * @var bool
     */
    private $dirty = false;

    /**
     * @param string[] $descriptions consent text by language ID
     * @param ConsentLink[] $additionalLinks
     */
    public function __construct(?ConsentLink $link = null, array $descriptions = [], ?ConsentRequirementType $requirementType = null, array $additionalLinks = [], ?\DateTimeImmutable $dateUpdated = null)
    {
        $this->link = $link ?? new ConsentLink();
        $this->descriptions = $descriptions;
        $this->requirementType = $requirementType;
        $this->additionalLinks = $additionalLinks;
        $this->dateUpdated = $dateUpdated;
    }

    public static function normalize(self $consent): ?string
    {
        return $consent->getId();
    }

    public function getLink(): ConsentLink
    {
        return $this->link;
    }

    public function setLink(ConsentLink $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->link->getId();
    }

    public function getLinkLabel(int $languageId): ?string
    {
        return $this->link->getLabel($languageId);
    }

    /**
     * @return array<int, string> consent text by language ID
     */
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

    public function getRequirementType(): ?ConsentRequirementType
    {
        return $this->requirementType;
    }

    public function setRequirementType(?ConsentRequirementType $requirementType): self
    {
        $this->dirty = $this->dirty || $this->requirementType !== $requirementType;
        $this->requirementType = $requirementType;

        return $this;
    }

    /**
     * @return ConsentLink[]
     */
    public function getAdditionalLinks(): array
    {
        return $this->additionalLinks;
    }

    public function addAdditionalLink(ConsentLink $link): self
    {
        if (!\in_array($link, $this->additionalLinks, true)) {
            $this->dirty = true;
            $this->additionalLinks[] = $link;
        }

        return $this;
    }

    public function removeAdditionalLink(ConsentLink $link): self
    {
        $key = array_search($link, $this->additionalLinks, true);

        if (false !== $key) {
            $this->dirty = true;
            unset($this->additionalLinks[$key]);
        }

        return $this;
    }

    public function getDateUpdated(): ?\DateTimeImmutable
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(?\DateTimeImmutable $dateUpdated): self
    {
        if (null === $this->dateUpdated || $this->isDirty()) {
            $this->dateUpdated = $dateUpdated;
            $this->onUpdated();
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
            'link' => $this->link,
            'descriptions' => $this->descriptions,
            'requirementType' => $this->requirementType,
            'additionalLinks' => $this->additionalLinks,
            'dateUpdated' => null !== $this->dateUpdated ? $this->dateUpdated->format(\DateTime::RFC3339) : null,
        ];
    }

    public function denormalize(DenormalizerInterface $denormalizer, $data, $format = null, array $context = []): void
    {
        if (!\is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array.');
        }

        if (!isset($data['descriptions'])) {
            throw new UnexpectedValueException('Normalized data does not contain all of the required parameters.');
        }

        if (isset($data['link'])) {
            $this->link = $denormalizer->denormalize($data['link'], ConsentLink::class, $format, $context);
        } else {
            $this->link = new ConsentLink($data['id'] ?? null, $data['cmsPageId'] ?? null);
        }

        $this->descriptions = $data['descriptions'];
        $this->requirementType = isset($data['requirementType']) ? ConsentRequirementType::tryFrom($data['requirementType']) : null;
        $this->additionalLinks = isset($data['additionalLinks']) ? $denormalizer->denormalize($data['additionalLinks'], ConsentLink::class . '[]', $format, $context) : [];
        $this->dateUpdated = isset($data['dateUpdated']) ? $denormalizer->denormalize($data['dateUpdated'], \DateTimeImmutable::class, $format, $context) : null;
    }

    public function __clone()
    {
        $this->link = clone $this->link;
        $this->additionalLinks = array_map(static function (ConsentLink $link): ConsentLink {
            return clone $link;
        }, $this->additionalLinks);
    }

    private function isDirty(): bool
    {
        if ($this->dirty) {
            return true;
        }

        if ($this->link->isDirty()) {
            return true;
        }

        foreach ($this->additionalLinks as $link) {
            if ($link->isDirty()) {
                return true;
            }
        }

        return false;
    }

    private function onUpdated(): void
    {
        $this->link->onConsentUpdated();

        foreach ($this->additionalLinks as $link) {
            $link->onConsentUpdated();
        }

        $this->dirty = false;
    }
}
