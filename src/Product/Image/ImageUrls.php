<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Image;

use izi\prestashop\Common\Product\ProductImage;

final class ImageUrls
{
    /**
     * @var string|null
     */
    private $mainImageUrl;

    /**
     * @var ProductImage[]
     */
    private $additionalImages;

    /**
     * @param ProductImage[] $additionalImages
     */
    public function __construct(?string $mainImageUrl, array $additionalImages)
    {
        $this->mainImageUrl = $mainImageUrl;
        $this->additionalImages = $additionalImages;
    }

    public static function createEmpty(): self
    {
        return new self(null, []);
    }

    public function getMainImageUrl(): ?string
    {
        return $this->mainImageUrl;
    }

    /**
     * @return ProductImage[]
     */
    public function getAdditionalImages(): array
    {
        return $this->additionalImages;
    }
}
