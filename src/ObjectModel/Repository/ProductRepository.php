<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\QueryBuilder;
use izi\prestashop\Product\ProductWithCombination;

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

    public function isAvailableOutOfStock(int $productId, ?int $shopId = null): bool
    {
        $outOfStock = \StockAvailable::outOfStock($productId, $shopId);

        return (bool) \Product::isAvailableWhenOutOfStock($outOfStock);
    }

    public function getAvailableStockQuantity(int $productId, ?int $combinationId = null, ?int $shopId = null): int
    {
        return (int) \StockAvailable::getQuantityAvailableByProduct($productId, $combinationId, $shopId);
    }

    public function getAvailableQuantity(int $productId, ?int $combinationId = null, ?\Cart $cart = null, ?int $customizationId = null): int
    {
        return (int) \Product::getQuantity($productId, $combinationId, null, $cart, $customizationId);
    }

    /**
     * @param bool $useDefaultCombination whether a default combination should be returned if the product has combinations
     *                                    but no combination ID has been passed
     */
    public function findWithCombination(int $productId, ?int $combinationId = null, ?int $languageId = null, ?int $shopId = null, bool $useDefaultCombination = false): ?ProductWithCombination
    {
        if (null === $product = $this->find($productId, $languageId, $shopId)) {
            return null;
        }

        if (!$product->cache_default_attribute) {
            return null === $combinationId ? new ProductWithCombination($product, null) : null;
        }

        if (null === $combinationId && !$useDefaultCombination) {
            return null;
        }

        $combinationId = $combinationId ?? (int) $product->cache_default_attribute;
        $combination = $this->manager->getRepository(\Combination::class)->find($combinationId, null, $shopId);

        if (null === $combination || (int) $combination->id_product !== $productId) {
            return null;
        }

        return new ProductWithCombination($product, $combination);
    }

    public function createSearchQueryBuilder(string $query, int $languageId, int $shopId): QueryBuilder
    {
        $query = pSQL($query);

        return $this
            ->createQueryBuilder('p', $languageId, $shopId)
            ->where('pl.name LIKE "%' . $query . '%" OR p.reference LIKE "%' . $query . '%"')
            ->where('p_shop.active = 1')
            ->where('p_shop.available_for_order = 1')
            ->where('p_shop.customizable <> 2')
            ->where('p.state = ' . \Product::STATE_SAVED);
    }

    public function getProductNameByProductId(int $productId, int $languageId, ?int $combinationId = null): ?string
    {
        $name = \Product::getProductName($productId, $combinationId, $languageId);

        return false === $name ? null : $name;
    }
}
