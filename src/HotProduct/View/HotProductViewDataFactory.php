<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\View;

use izi\prestashop\BasketApp\Product\ProductsApiClientInterface;
use izi\prestashop\BasketApp\Product\Response\Product;
use izi\prestashop\HotProduct\Exception\InvalidProductDataException;
use izi\prestashop\HotProduct\HotProduct;
use izi\prestashop\HotProduct\HotProductValidator;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use izi\prestashop\Product\ReferenceId;

final class HotProductViewDataFactory
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ObjectRepositoryInterface<\Shop>
     */
    private $shopRepository;

    /**
     * @var ProductsApiClientInterface
     */
    private $client;

    /**
     * @var HotProductValidator
     */
    private $validator;

    /**
     * @var array<int, \Shop> shops by ID
     */
    private $shopMap = [];

    /**
     * @param ProductRepository $productRepository
     * @param ObjectRepositoryInterface<\Shop> $shopRepository
     */
    public function __construct(\Context $context, ObjectRepositoryInterface $productRepository, ObjectRepositoryInterface $shopRepository, ProductsApiClientInterface $client)
    {
        $this->context = $context;
        $this->productRepository = $productRepository;
        $this->shopRepository = $shopRepository;
        $this->client = $client;
        $this->validator = new HotProductValidator($productRepository);
    }

    /**
     * @param HotProduct[] $products
     */
    public function createForProducts(array $products): HotProductListView
    {
        try {
            $apiProducts = iterator_to_array($this->client->getProducts());
        } catch (\Exception $e) {
            $viewModels = array_map([$this, 'createViewModelForLocalProduct'], $products);

            return new HotProductListView($viewModels, false);
        }

        $apiProductMap = [];
        foreach ($apiProducts as $apiProduct) {
            $apiProductMap[$apiProduct->getId()] = $apiProduct;
        }

        $viewModels = array_map(function (HotProduct $product) use (&$apiProductMap) {
            $referenceId = (string) $product->getReferenceId();

            if (!array_key_exists($referenceId, $apiProductMap)) {
                return $this->createViewModelForLocalProduct($product, null, false);
            }

            $apiProduct = $apiProductMap[$referenceId];
            unset($apiProductMap[$referenceId]);

            return $this->createViewModelForLocalProduct($product, $apiProduct, true);
        }, $products);

        return new HotProductListView(array_merge($viewModels, array_map(
            [$this, 'createViewModelForRemoteProduct'],
            array_values($apiProductMap)
        )), true);
    }

    private function createViewModelForLocalProduct(HotProduct $product, ?Product $apiProduct = null, ?bool $exists = null): HotProductView
    {
        $name = $this->productRepository->getProductNameByProductId(
            $product->getProductId(),
            (int) $this->context->language->id,
            $product->getCombinationId()
        );
        $shop = $this->getShopName($product);

        return false === $exists
            ? HotProductView::notFound($product, $name, $shop)
            : HotProductView::local($product, $name, $shop, $apiProduct);
    }

    private function createViewModelForRemoteProduct(Product $product): HotProductView
    {
        $importable = $this->isImportable($product);

        return HotProductView::remote($product, $importable);
    }

    private function getShopName(HotProduct $product): ?string
    {
        if (null === $shopId = $product->getShopId()) {
            return null;
        }

        if (!array_key_exists($shopId, $this->shopMap)) {
            $this->shopMap[$shopId] = $this->shopRepository->find($shopId);
        }

        if (null === $shop = $this->shopMap[$shopId]) {
            throw new \RuntimeException('Shop not found.');
        }

        return $shop->name ?? 'Shop #' . $shopId;
    }

    private function isImportable(Product $apiProduct): bool
    {
        if (null === $referenceId = ReferenceId::fromString($apiProduct->getId())) {
            return false;
        }

        if ($referenceId->hasCustomization()) {
            return false;
        }

        try {
            $this->validator->validate(new HotProduct(
                $referenceId->getProductId(),
                $referenceId->getCombinationId(),
                (int) $this->context->shop->id
            ), true);

            return true;
        } catch (InvalidProductDataException $e) {
            return false;
        }
    }
}
