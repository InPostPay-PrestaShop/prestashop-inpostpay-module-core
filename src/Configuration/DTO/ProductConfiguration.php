<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\Product\Description\DescriptionSource;
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

    /**
     * @var DescriptionSource
     */
    private $descriptionSource;

    public function __construct(?int $normalImageTypeId, ?int $smallImageTypeId, ?int $largeImageTypeId, ?ImageGalleryType $defaultImageGalleryType = null, ?DescriptionSource $descriptionSource = null)
    {
        $this->normalImageTypeId = $normalImageTypeId;
        $this->smallImageTypeId = $smallImageTypeId;
        $this->largeImageTypeId = $largeImageTypeId;
        $this->defaultImageGalleryType = $defaultImageGalleryType ?? ImageGalleryType::AllImages();
        $this->descriptionSource = $descriptionSource ?? DescriptionSource::LongWithShortFallback();
    }

    public function getNormalImageTypeId(?int $shopId = null): ?int
    {
        return $this->normalImageTypeId;
    }

    public function setNormalImageTypeId(?int $imageTypeId): void
    {
        $this->normalImageTypeId = $imageTypeId;
    }

    public function getSmallImageTypeId(?int $shopId = null): ?int
    {
        return $this->smallImageTypeId;
    }

    public function setSmallImageTypeId(?int $imageTypeId): void
    {
        $this->smallImageTypeId = $imageTypeId;
    }

    public function getLargeImageTypeId(?int $shopId = null): ?int
    {
        return $this->largeImageTypeId;
    }

    public function setLargeImageTypeId(?int $imageTypeId): void
    {
        $this->largeImageTypeId = $imageTypeId;
    }

    public function getDefaultImageGalleryType(?int $shopId = null): ImageGalleryType
    {
        return $this->defaultImageGalleryType;
    }

    public function setDefaultImageGalleryType(ImageGalleryType $galleryType): void
    {
        $this->defaultImageGalleryType = $galleryType;
    }

    public function getDescriptionSource(?int $shopId = null): DescriptionSource
    {
        return $this->descriptionSource;
    }

    public function setDescriptionSource(DescriptionSource $descriptionSource): void
    {
        $this->descriptionSource = $descriptionSource;
    }
}
