<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Product\Response;

final class CreateProductsResponse implements \JsonSerializable
{
    /**
     * @var ProductId[]
     */
    private $content;

    /**
     * @param ProductId[] $content
     */
    public function __construct(array $content)
    {
        $this->content = $content;
    }

    /**
     * @return ProductId[]
     */
    public function getProductIds(): array
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
