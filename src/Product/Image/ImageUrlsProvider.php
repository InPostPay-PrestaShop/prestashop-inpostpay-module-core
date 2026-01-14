<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Image;

use izi\prestashop\Common\Product\ProductImage;
use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ProductOptions\ProductOptionsRepositoryInterface;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;

/**
 * @phpstan-type ImageTypes array{small: string, normal: string, large: string}
 */
final class ImageUrlsProvider implements ImageUrlsProviderInterface
{
    /**
     * @var ImageRetriever
     */
    private $imageRetriever;

    /**
     * @var ProductConfigurationInterface
     */
    private $productConfiguration;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ObjectRepositoryInterface
     */
    private $imageTypeRepository;

    /**
     * @var ProductOptionsRepositoryInterface
     */
    private $productOptionsRepository;

    /**
     * @var array<int, ImageTypes>
     */
    private $imageTypes = [];

    /**
     * @param ObjectRepositoryInterface<\ImageType> $imageTypeRepository
     */
    public function __construct(ImageRetriever $imageRetriever, ProductConfigurationInterface $productConfiguration, \Context $context, ObjectRepositoryInterface $imageTypeRepository, ProductOptionsRepositoryInterface $productOptionsRepository)
    {
        $this->imageRetriever = $imageRetriever;
        $this->productConfiguration = $productConfiguration;
        $this->context = $context;
        $this->imageTypeRepository = $imageTypeRepository;
        $this->productOptionsRepository = $productOptionsRepository;
    }

    public function getImageUrls(int $productId, ?int $combinationId, ?\Language $language = null, ?int $shopId = null): ImageUrls
    {
        $images = $this->imageRetriever->getProductImages([
            'id_product' => $productId,
            'id_product_attribute' => (int) $combinationId,
        ], $language ?? $this->context->language);

        if ([] === $images) {
            return ImageUrls::createEmpty();
        }

        $shopId = $shopId ?? (int) $this->context->shop->id;
        $cover = $this->getCover($images);

        if (ImageGalleryType::OnlyCoverImage() === $this->getGalleryType($productId, $shopId)) {
            $images = [$cover];
        } else {
            array_unshift($images, $cover);
        }

        $coverUrl = $this->getCoverUrl($cover, $shopId);
        $additionalImages = $this->getAdditionalImages($images, $shopId);

        return new ImageUrls($coverUrl, $additionalImages);
    }

    private function getCover(array &$images): array
    {
        foreach ($images as $key => $image) {
            if (!empty($image['cover'])) {
                unset($images[$key]);

                return $image;
            }
        }

        return array_shift($images);
    }

    private function getCoverUrl(array $image, int $shopId): ?string
    {
        $imageType = $this->getImageType($shopId, 'normal');
        $image = $image['bySize'][$imageType] ?? $image['small'];

        return $image['url'];
    }

    /**
     * @return ProductImage[]
     */
    private function getAdditionalImages(array $images, int $shopId): array
    {
        $images = \array_slice($images, 0, 10);
        $imageTypes = $this->getImageTypes($shopId);

        return array_map(static function (array $image) use ($imageTypes): ProductImage {
            $smallSize = $image['bySize'][$imageTypes['small']] ?? $image['medium'];
            $normalSize = $image['bySize'][$imageTypes['large']] ?? $image['large'];

            return new ProductImage($smallSize['url'], $normalSize['url']);
        }, $images);
    }

    private function getImageType(int $shopId, string $name): string
    {
        return $this->getImageTypes($shopId)[$name];
    }

    /**
     * @return ImageTypes
     */
    private function getImageTypes(int $shopId): array
    {
        return $this->imageTypes[$shopId] ?? $this->imageTypes[$shopId] = [
            'small' => $this->getTypeNameById($this->productConfiguration->getSmallImageTypeId($shopId), 'home_default'),
            'normal' => $this->getTypeNameById($this->productConfiguration->getNormalImageTypeId($shopId), 'cart_default'),
            'large' => $this->getTypeNameById($this->productConfiguration->getLargeImageTypeId($shopId), 'medium_default'),
        ];
    }

    private function getTypeNameById(?int $imageTypeId, string $default): string
    {
        if (null === $imageTypeId) {
            return $default;
        }

        if (null === $imageType = $this->imageTypeRepository->find($imageTypeId)) {
            return $default;
        }

        return $imageType->name ?? $default;
    }

    private function getGalleryType(int $productId, int $shopId): ImageGalleryType
    {
        $options = $this->productOptionsRepository->find($productId);

        if (null !== $options && $galleryType = $options->getImageGalleryType()) {
            return $galleryType;
        }

        return $this->productConfiguration->getDefaultImageGalleryType($shopId);
    }
}
