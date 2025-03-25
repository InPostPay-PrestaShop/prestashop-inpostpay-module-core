<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Product\Request;

use izi\prestashop\Common\HotProduct\IdentifiableProduct;

final class CreateProductsRequest implements \JsonSerializable
{
    /**
     * @var IdentifiableProduct[]
     */
    private $content;

    /**
     * @param IdentifiableProduct[] $content
     */
    public function __construct(array $content)
    {
        $this->content = $content;
    }

    /**
     * @return IdentifiableProduct[]
     */
    public function getProducts(): array
    {
        return $this->content;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
