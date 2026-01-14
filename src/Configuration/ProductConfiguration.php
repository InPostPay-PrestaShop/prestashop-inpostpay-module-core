<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Product\Image\ImageGalleryType;

/**
 * @implements PersistentConfigurationInterface<ProductConfigurationInterface>
 */
final class ProductConfiguration implements ProductConfigurationInterface, PersistentConfigurationInterface
{
    private const IMAGE_NORMAL_TYPE = 'INPOST_PAY_PRODUCT_IMAGE_NORMAL_TYPE';
    private const IMAGE_SMALL_TYPE = 'INPOST_PAY_PRODUCT_IMAGE_SMALL_TYPE';
    private const IMAGE_LARGE_TYPE = 'INPOST_PAY_PRODUCT_IMAGE_LARGE_TYPE';
    private const DEFAULT_IMAGE_GALLERY_TYPE = 'INPOST_PAY_PRODUCT_DEFAULT_IMAGE_GALLERY_TYPE';

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    public function __construct(ShopAwareConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getNormalImageTypeId(?int $shopId = null): ?int
    {
        return (int) $this->configuration->get(self::IMAGE_NORMAL_TYPE, $shopId);
    }

    public function getSmallImageTypeId(?int $shopId = null): ?int
    {
        return (int) $this->configuration->get(self::IMAGE_SMALL_TYPE, $shopId);
    }

    public function getLargeImageTypeId(?int $shopId = null): ?int
    {
        return (int) $this->configuration->get(self::IMAGE_LARGE_TYPE, $shopId);
    }

    public function getDefaultImageGalleryType(?int $shopId = null): ImageGalleryType
    {
        $value = (int) $this->configuration->get(self::DEFAULT_IMAGE_GALLERY_TYPE, $shopId);

        return ImageGalleryType::tryFrom($value) ?? ImageGalleryType::AllImages();
    }

    public function copy(): ProductConfigurationInterface
    {
        return new DTO\ProductConfiguration(
            $this->getNormalImageTypeId(),
            $this->getSmallImageTypeId(),
            $this->getLargeImageTypeId(),
            $this->getDefaultImageGalleryType()
        );
    }

    public function persist(ProductConfigurationInterface $configuration): void
    {
        $this->configuration->set(self::IMAGE_NORMAL_TYPE, $configuration->getNormalImageTypeId());
        $this->configuration->set(self::IMAGE_SMALL_TYPE, $configuration->getSmallImageTypeId());
        $this->configuration->set(self::IMAGE_LARGE_TYPE, $configuration->getLargeImageTypeId());
        $this->configuration->set(self::DEFAULT_IMAGE_GALLERY_TYPE, $configuration->getDefaultImageGalleryType()->value);
    }
}
