<?php

declare(strict_types=1);

namespace izi\prestashop\Product;

final class ProductWithCombination
{
    /**
     * @var \Product
     */
    private $product;

    /**
     * @var \Combination|null
     */
    private $combination;

    public function __construct(\Product $product, ?\Combination $combination)
    {
        if (null !== $combination && (int) $combination->id_product !== (int) $product->id) {
            throw new \InvalidArgumentException('Combination does not belong to the product.');
        }

        $this->product = $product;
        $this->combination = $combination;
    }

    public function getProduct(): \Product
    {
        return $this->product;
    }

    public function getCombination(): ?\Combination
    {
        return $this->combination;
    }

    public function hasCombination(): bool
    {
        return null !== $this->combination;
    }
}
