<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Configuration\DTO\Product\ProductRestrictions;

interface ProductRestrictionsProviderInterface
{
    public function getProductRestrictions(): ?ProductRestrictions;
}
