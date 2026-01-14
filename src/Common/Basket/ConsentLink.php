<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

final class ConsentLink implements \JsonSerializable
{
    public const PLACEHOLDER_PREFIX = '#';

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $consent_link;

    /**
     * @var string|null
     */
    private $label_link;

    public function __construct(string $id, string $consent_link, ?string $label_link = null)
    {
        $this->id = $id;
        $this->consent_link = $consent_link;
        $this->label_link = $label_link;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLink(): string
    {
        return $this->consent_link;
    }

    public function getLinkLabel(): ?string
    {
        return $this->label_link;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
