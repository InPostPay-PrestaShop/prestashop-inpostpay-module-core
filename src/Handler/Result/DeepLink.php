<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Result;

/**
 * @deprecated
 */
final class DeepLink implements \JsonSerializable
{
    private $link;

    public function __construct(string $link)
    {
        $this->link = $link;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function jsonSerialize(): array
    {
        return ['link' => $this->link];
    }
}
