<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Description;

interface ProductDescriptionProviderInterface
{
    public function getDescription(\Product $product, ?int $shopId = null): string;
}
