<?php

declare(strict_types=1);

namespace izi\prestashop\Repository\Product;

interface FeatureRestrictionsRepositoryInterface
{
    /**
     * @param int[] $featureIds
     */
    public function isAnyFeatureRestricted(array $featureIds, ?int $shopId = null): bool;

    public function hasFeatureRestrictions(?int $shopId = null): bool;
}
