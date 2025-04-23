<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct;

use izi\prestashop\Common\HotProduct\Product;

interface HotProductDataMapperInterface
{
    public function map(HotProduct $hotProduct): Product;
}
