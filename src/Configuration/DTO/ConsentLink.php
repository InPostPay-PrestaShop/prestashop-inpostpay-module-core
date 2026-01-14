<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class ConsentLink implements \JsonSerializable
{
    /**
     * @var string|null
     *
     * @Assert\NotBlank
     * @Assert\Length(max = Uuid::CANONICAL_FORMAT_LENGTH)
     */
    private $id;

    /**
     * @var int|null
     *
     * @Assert\NotNull
     */
    private $cmsPageId;

    /**
     * @var string[]
     *
     * @Assert\All({
     *     @Assert\Length(min = 1),
     * })
     */
    private $labels;

    /**
     * @var bool
     */
    private $dirty = false;

    /**
     * @param string[] $labels label by language ID
     */
    public function __construct(?string $id = null, ?int $cmsPageId = null, array $labels = [])
    {
        $this->id = $id;
        $this->cmsPageId = $cmsPageId;
        $this->labels = $labels;
    }

    public static function normalize(self $link): ?string
    {
        return $link->getId();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCmsPageId(): ?int
    {
        return $this->cmsPageId;
    }

    public function setCmsPageId(?int $cmsPageId): self
    {
        $this->dirty = $this->dirty || $this->cmsPageId !== $cmsPageId;
        $this->cmsPageId = $cmsPageId;

        return $this;
    }

    /**
     * @return array<int, string> label by language ID
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    public function getLabel(int $languageId): ?string
    {
        return $this->labels[$languageId] ?? null;
    }

    /**
     * @param array<int, string> $labels label by language ID
     */
    public function setLabels(array $labels): self
    {
        $this->dirty = $this->dirty || $this->labels !== $labels;
        $this->labels = $labels;

        return $this;
    }

    public function isDirty(): bool
    {
        return $this->dirty;
    }

    public function onConsentUpdated(): void
    {
        $this->dirty = false;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'cmsPageId' => $this->cmsPageId,
            'labels' => $this->labels,
        ];
    }
}
