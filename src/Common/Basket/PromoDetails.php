<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

final class PromoDetails implements \JsonSerializable
{
    /**
     * @var string
     */
    private $link;

    public function __construct(string $link)
    {
        $this->link = $link;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
