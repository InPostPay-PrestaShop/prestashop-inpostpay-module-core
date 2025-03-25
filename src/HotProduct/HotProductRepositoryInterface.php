<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct;

use izi\prestashop\Product\ReferenceId;

interface HotProductRepositoryInterface
{
    public function add(HotProduct $product): void;

    public function find(int $id): ?HotProduct;

    /**
     * @return HotProduct[]
     */
    public function findAll(?int $shopId = null): array;

    /**
     * @param string[]|ReferenceId[] $referenceIds
     */
    public function findBy(?int $shopId = null, ?int $limit = null, ?int $offset = null, array $referenceIds = []): array;

    /**
     * @param string[]|ReferenceId[] $referenceIds
     */
    public function countBy(?int $shopId = null, array $referenceIds = []): int;

    public function findOneByReferenceId(string $referenceId, ?int $shopId = null): ?HotProduct;

    public function findOneByProductId(int $productId, ?int $combinationId = null, ?int $shopId = null): ?HotProduct;

    public function update(HotProduct $product): void;

    public function remove(HotProduct $product): void;
}
