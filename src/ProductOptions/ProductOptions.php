<?php

declare(strict_types=1);

namespace izi\prestashop\ProductOptions;

use izi\prestashop\Product\Image\ImageGalleryType;

class ProductOptions
{
    /**
     * @var int ID of {@see \Product}
     */
    private $productId;

    /**
     * @var ImageGalleryType|null
     */
    private $imageGalleryType;

    public function __construct(int $productId)
    {
        if (0 >= $productId) {
            throw new \DomainException('Product ID must be greater than 0.');
        }

        $this->productId = $productId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getImageGalleryType(): ?ImageGalleryType
    {
        return $this->imageGalleryType;
    }

    /**
     * @return $this
     */
    public function setImageGalleryType(?ImageGalleryType $imageGalleryType): self
    {
        $this->imageGalleryType = $imageGalleryType;

        return $this;
    }
}
