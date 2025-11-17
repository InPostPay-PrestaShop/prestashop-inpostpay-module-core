<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\Product\Image\ImageGalleryType;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductConfiguration implements ProductConfigurationInterface
{
    /**
     * @var int|null
     *
     * @Assert\NotNull
     */
    private $normalImageTypeId;

    /**
     * @var int|null
     *
     * @Assert\NotNull
     */
    private $smallImageTypeId;

    /**
     * @var int|null
     *
     * @Assert\NotNull
     */
    private $largeImageTypeId;

    /**
     * @var ImageGalleryType
     */
    private $defaultImageGalleryType;

    public function __construct(?int $normalImageTypeId, ?int $smallImageTypeId, ?int $largeImageTypeId, ?ImageGalleryType $defaultImageGalleryType = null)
    {
        $this->normalImageTypeId = $normalImageTypeId;
        $this->smallImageTypeId = $smallImageTypeId;
        $this->largeImageTypeId = $largeImageTypeId;
        $this->defaultImageGalleryType = $defaultImageGalleryType ?? ImageGalleryType::AllImages();
    }

    public function getNormalImageTypeId(?int $shopId = null): ?int
    {
        return $this->normalImageTypeId;
    }

    public function setNormalImageTypeId(?\ImageType $imageType): void
    {
        $this->normalImageTypeId = null === $imageType ? null : (int) $imageType->id;
    }

    public function getSmallImageTypeId(?int $shopId = null): ?int
    {
        return $this->smallImageTypeId;
    }

    public function setSmallImageTypeId(?\ImageType $imageType): void
    {
        $this->smallImageTypeId = null === $imageType ? null : (int) $imageType->id;
    }

    public function getLargeImageTypeId(?int $shopId = null): ?int
    {
        return $this->largeImageTypeId;
    }

    public function setLargeImageTypeId(?\ImageType $imageType): void
    {
        $this->largeImageTypeId = null === $imageType ? null : (int) $imageType->id;
    }

    public function getDefaultImageGalleryType(?int $shopId = null): ImageGalleryType
    {
        return $this->defaultImageGalleryType;
    }

    public function setDefaultImageGalleryType(ImageGalleryType $galleryType): void
    {
        $this->defaultImageGalleryType = $galleryType;
    }
}
