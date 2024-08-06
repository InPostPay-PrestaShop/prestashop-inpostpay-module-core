<?php

declare(strict_types=1);

namespace izi\prestashop\Repository;

use izi\prestashop\Configuration\DTO\Product\ProductRestrictions;
use izi\prestashop\Repository\Product\AttributeRestrictionsRepositoryInterface;
use izi\prestashop\Repository\Product\CategoryRestrictionsRepositoryInterface;
use izi\prestashop\Repository\Product\ManufacturerRestrictionsRepositoryInterface;

interface ProductRestrictionsRepositoryInterface extends CategoryRestrictionsRepositoryInterface, ManufacturerRestrictionsRepositoryInterface, AttributeRestrictionsRepositoryInterface
{
    public function getProductRestrictions(?int $shopId = null): ProductRestrictions;

    public function updateProductRestrictions(ProductRestrictions $productRestrictions, ?int $shopId = null);
}
