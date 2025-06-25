<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\HotProduct\Product;
use izi\prestashop\Common\HotProduct\ProductAvailability;
use izi\prestashop\Common\HotProduct\Quantity;
use izi\prestashop\Common\Product\ProductAttribute;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\ObjectModel\Repository\CombinationRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use izi\prestashop\Product\Image\ImageUrlsProviderInterface;
use izi\prestashop\Product\Price\PriceCalculatorInterface;
use izi\prestashop\Product\Price\PriceQuery;
use izi\prestashop\Product\Util\DescriptionFormatter;

final class HotProductDataMapper implements HotProductDataMapperInterface
{
    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    /**
     * @var ObjectRepositoryInterface<\Language>
     */
    private $languageRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CombinationRepository
     */
    private $combinationRepository;

    /**
     * @var PriceCalculatorInterface
     */
    private $priceCalculator;

    /**
     * @var ImageUrlsProviderInterface
     */
    private $imageProvider;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var array<int, \Language> Polish language by shop ID
     */
    private $languages = [];

    /**
     * @param ObjectRepositoryInterface<\Language> $languageRepository
     * @param ProductRepository $productRepository
     * @param CombinationRepository $combinationRepository
     */
    public function __construct(PrestaShopConfiguration $configuration, ObjectRepositoryInterface $languageRepository, ObjectRepositoryInterface $productRepository, ObjectRepositoryInterface $combinationRepository, PriceCalculatorInterface $priceCalculator, ImageUrlsProviderInterface $imageProvider, ?\Context $context = null)
    {
        $this->configuration = $configuration;
        $this->languageRepository = $languageRepository;
        $this->productRepository = $productRepository;
        $this->combinationRepository = $combinationRepository;
        $this->priceCalculator = $priceCalculator;
        $this->imageProvider = $imageProvider;
        $this->context = $context ?? \Context::getContext();
    }

    public function map(HotProduct $hotProduct): Product
    {
        $shopId = $hotProduct->getShopId() ?? $this->configuration->getDefaultShopId();

        $language = $this->getPolishLanguage($shopId);
        $languageId = (int) $language->id;

        $productWithCombination = $this->productRepository->findWithCombination(
            $productId = $hotProduct->getProductId(),
            $hotProduct->getCombinationId(),
            $languageId,
            $shopId,
            true
        );

        if (null === $productWithCombination) {
            throw new \RuntimeException('Product not found.');
        }

        $product = $productWithCombination->getProduct();
        $combination = $productWithCombination->getCombination();
        $combinationId = $combination ? (int) $combination->id : null;

        $imageUrls = $this->imageProvider->getImageUrls($productId, $combinationId, $language, $shopId);

        $currency = Currency::getDefault();
        $price = $this->priceCalculator->calculatePrice(new PriceQuery(
            $productId,
            $shopId,
            $currency,
            $combinationId
        ));

        if (HotProductValidator::isAvailableForOrder($product)) {
            $quantity = $this->getAvailableQuantity($product, $combination, $shopId);
            $availability = $this->getAvailability($hotProduct);
        } else {
            // Should be deleted, but we might have not been able to react to the associated update event (or the hot product was created outside PrestaShop).
            // Passing 0 as available quantity and a past end of availability date should cause the product to become inactive on the InPost side.
            $quantity = 0;
            $availability = new ProductAvailability(null, new \DateTimeImmutable('yesterday'));
        }

        $ean = $combination && $combination->ean13 ? $combination->ean13 : $product->ean13;

        return new Product(
            \Tools::substr($product->name ?? '', 0, 255),
            DescriptionFormatter::formatDescription($product),
            $imageUrls->getMainImageUrl(),
            $price,
            $currency,
            Quantity::integer($quantity),
            (string) $ean,
            $availability,
            $imageUrls->getAdditionalImages(),
            $this->getAttributes($combination, $languageId),
            $this->context->link->getProductLink($product, null, null, null, $languageId, $shopId, $combinationId)
        );
    }

    private function getAvailability(HotProduct $product): ?ProductAvailability
    {
        $from = $product->getAvailableFrom();
        $to = $product->getAvailableTo();

        if (null === $from && null === $to) {
            return null;
        }

        return new ProductAvailability($from, $to);
    }

    private function getAvailableQuantity(\Product $product, ?\Combination $combination, int $shopId): int
    {
        $productId = (int) $product->id;
        $combinationId = $combination ? (int) $combination->id : null;

        $stockQuantity = $this->productRepository->getAvailableStockQuantity($productId, $combinationId, $shopId);

        if ($this->productRepository->isAvailableOutOfStock($productId, $shopId)) {
            return max(9999, $stockQuantity);
        }

        $minQuantity = $combination ? (int) $combination->minimal_quantity : (int) $product->minimal_quantity;

        if ($stockQuantity < $minQuantity) {
            return 0;
        }

        return $stockQuantity;
    }

    /**
     * @return ProductAttribute[]
     */
    private function getAttributes(?\Combination $combination, int $languageId): array
    {
        if (null === $combination) {
            return [];
        }

        if ([] === $productAttributes = $this->combinationRepository->getAttributesByCombinationId((int) $combination->id, $languageId)) {
            return [];
        }

        $result = [];

        foreach ($productAttributes as $attribute) {
            $group = trim($attribute->getGroup()->public_name ?: $attribute->getGroup()->name ?? '');
            $name = trim($attribute->getAttribute()->name ?? '');

            if ('' === $group || '' === $name) {
                continue;
            }

            $result[] = new ProductAttribute(
                \Tools::substr($group, 0, 255),
                \Tools::substr($name, 0, 255)
            );
        }

        return $result;
    }

    private function getPolishLanguage(int $shopId): \Language
    {
        if (!array_key_exists($shopId, $this->languages)) {
            $this->languages[$shopId] = $this->languageRepository->findOneBy([
                'iso_code' => 'pl',
                'id_shop' => $shopId,
            ], ['active' => 'DESC']);
        }

        if (null === $language = $this->languages[$shopId]) {
            throw new \RuntimeException('Polish language not found.');
        }

        return $language;
    }
}
