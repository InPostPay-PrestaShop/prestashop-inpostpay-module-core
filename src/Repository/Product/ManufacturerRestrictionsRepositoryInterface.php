<?php

declare(strict_types=1);

namespace izi\prestashop\Repository\Product;

interface ManufacturerRestrictionsRepositoryInterface
{
    public function isManufacturerRestricted(int $manufacturerId, ?int $shopId = null): bool;

    public function hasManufacturerRestrictions(?int $shopId = null): bool;
}
