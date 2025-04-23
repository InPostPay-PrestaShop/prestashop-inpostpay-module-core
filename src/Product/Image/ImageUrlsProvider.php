<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Image;

use izi\prestashop\Common\Product\ProductImage;
use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
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
     * @var array<int, ImageTypes>
     */
    private $imageTypes = [];

    /**
     * @param ObjectRepositoryInterface<\ImageType> $imageTypeRepository
     */
    public function __construct(ImageRetriever $imageRetriever, ProductConfigurationInterface $productConfiguration, \Context $context, ObjectRepositoryInterface $imageTypeRepository)
    {
        $this->imageRetriever = $imageRetriever;
        $this->productConfiguration = $productConfiguration;
        $this->context = $context;
        $this->imageTypeRepository = $imageTypeRepository;
    }

    /**
     * @internal
     */
    public static function create(?ImageRetriever $imageRetriever = null, \Context $context = null, ?ProductConfigurationInterface $configuration = null): self
    {
        /** @var \InPostIzi $module */
        $module = \Module::getInstanceByName('inpostizi');

        $context = $context ?? \Context::getContext();
        $imageRetriever = $imageRetriever ?? new ImageRetriever($context->link);
        $configuration = $configuration ?? $module->get(ProductConfigurationInterface::class);

        return new self(
            $imageRetriever,
            $configuration,
            $context,
            $module->get(ObjectManagerInterface::class)->getRepository(\ImageType::class)
        );
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

        $coverUrl = $this->getCoverUrl($images, $shopId);
        $additionalImages = $this->getAdditionalImages($images, $shopId);

        return new ImageUrls($coverUrl, $additionalImages);
    }

    private function getCoverUrl(array $images, int $shopId): ?string
    {
        if (null === $image = $this->getCover($images)) {
            return null;
        }

        $imageType = $this->getImageType($shopId, 'normal');
        $image = $image['bySize'][$imageType] ?? $image['small'];

        return $image['url'];
    }

    private function getCover(array $images): ?array
    {
        foreach ($images as $image) {
            if (!empty($image['cover'])) {
                return $image;
            }
        }

        if (false !== $image = reset($images)) {
            return $image;
        }

        return null;
    }

    /**
     * @return ProductImage[]
     */
    private function getAdditionalImages(array $images, int $shopId): array
    {
        $images = array_slice($images, 0, 10);
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
}
