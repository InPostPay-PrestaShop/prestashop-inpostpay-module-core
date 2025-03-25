<?php

namespace izi\prestashop\Product\Image;

interface ImageUrlsProviderInterface
{
    public function getImageUrls(int $productId, ?int $combinationId, ?\Language $language = null, ?int $shopId = null): ImageUrls;
}
