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

    /**
     * @internal
     */
    public static function getDefaultImageGalleryTypeFromConfig(ProductConfigurationInterface $configuration, ?int $shopId = null): ImageGalleryType
    {
        if (is_callable([$configuration, 'getDefaultImageGalleryType'])) {
            return $configuration->getDefaultImageGalleryType($shopId);
        }

        @trigger_error(sprintf('Not implementing "getDefaultImageGalleryType()" in "%s" is deprecated since 2.4.0.', get_class($configuration)), E_USER_DEPRECATED);

        return ImageGalleryType::AllImages();
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
        $this->setDefaultImageGalleryType($configuration);
    }

    private function setDefaultImageGalleryType(ProductConfigurationInterface $configuration): void
    {
        $galleryType = self::getDefaultImageGalleryTypeFromConfig($configuration);

        $this->configuration->set(self::DEFAULT_IMAGE_GALLERY_TYPE, $galleryType->value);
    }
}
