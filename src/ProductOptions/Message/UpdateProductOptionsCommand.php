<?php

declare(strict_types=1);

namespace izi\prestashop\ProductOptions\Message;

use izi\prestashop\Product\Image\ImageGalleryType;
use izi\prestashop\ProductOptions\MessageHandler\UpdateProductOptionsHandler;
use izi\prestashop\ProductOptions\ProductOptions;

/**
 * @see UpdateProductOptionsHandler
 */
final class UpdateProductOptionsCommand
{
    /**
     * @var int
     */
    private $productId;

    /**
     * @var ImageGalleryType|null
     */
    private $imageGalleryType;

    public function __construct(int $productId)
    {
        $this->productId = $productId;
    }

    public static function for(ProductOptions $options): self
    {
        $command = new self($options->getProductId());
        $command->imageGalleryType = $options->getImageGalleryType();

        return $command;
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
