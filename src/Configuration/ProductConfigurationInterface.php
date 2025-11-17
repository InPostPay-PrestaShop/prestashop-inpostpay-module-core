<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Product\Image\ImageGalleryType;

/**
 * @method ImageGalleryType getDefaultImageGalleryType(int|null $shopId = null)
 */
interface ProductConfigurationInterface
{
    public function getNormalImageTypeId(?int $shopId = null): ?int;

    public function getSmallImageTypeId(?int $shopId = null): ?int;

    public function getLargeImageTypeId(?int $shopId = null): ?int;
}
