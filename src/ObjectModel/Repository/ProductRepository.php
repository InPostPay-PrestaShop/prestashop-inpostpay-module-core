<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\Product>
 */
class ProductRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\Product::class, $manager);
    }

    public function productExists(int $idProduct): bool
    {
        return null !== $this->find($idProduct);
    }

    public function getDefaultCombinationId(int $productId): ?int
    {
        $combinationId = (int) \Product::getDefaultAttribute($productId);

        return 0 >= $combinationId ? null : $combinationId;
    }

    public function isAvailableOutOfStock(int $productId): bool
    {
        $outOfStock = \StockAvailable::outOfStock($productId);

        return (bool) \Product::isAvailableWhenOutOfStock($outOfStock);
    }

    public function getAvailableStockQuantity(int $productId, ?int $combinationId = null, ?int $shopId = null): int
    {
        return (int) \StockAvailable::getQuantityAvailableByProduct($productId, $combinationId, $shopId);
    }
}
