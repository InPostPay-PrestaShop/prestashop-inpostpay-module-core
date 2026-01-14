<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\BasketApp\Product\ProductsApiClientInterface;
use izi\prestashop\BasketApp\Product\Response\Product;
use izi\prestashop\Common\HotProduct\IdentifiableProduct;
use izi\prestashop\Common\HotProduct\ProductAvailability;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\HotProduct\HotProduct;
use izi\prestashop\HotProduct\HotProductDataMapperInterface;
use izi\prestashop\HotProduct\HotProductRepositoryInterface;
use izi\prestashop\MerchantApi\Command\GetProductsCommand;
use izi\prestashop\MerchantApi\Model\Product\Response\Products;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use izi\prestashop\Product\ReferenceId;
use Psr\SimpleCache\CacheInterface;

final class GetProductsHandler implements GetProductsHandlerInterface
{
    use CommandHandlerTrait;

    private const IMPORTABLE_CACHE_KEY = 'IMPORTABLE_HOT_PRODUCTS_MAP';
    private const CACHE_TTL = 3600; // 1 hour
    private const DEFAULT_PAGE_SIZE = 20;

    /**
     * @var HotProductRepositoryInterface
     */
    private $repository;

    /**
     * @var HotProductDataMapperInterface
     */
    private $dataMapper;

    /**
     * @var ProductsApiClientInterface
     */
    private $client;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var array<string, Product> API products by ID
     */
    private $apiProducts = [];

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(HotProductRepositoryInterface $repository, HotProductDataMapperInterface $dataMapper, ProductsApiClientInterface $client, CacheInterface $cache, ObjectRepositoryInterface $productRepository)
    {
        $this->repository = $repository;
        $this->dataMapper = $dataMapper;
        $this->client = $client;
        $this->cache = $cache;
        $this->productRepository = $productRepository;
    }

    public function __invoke(GetProductsCommand $command): Products
    {
        $shopId = $command->getShopId();
        $productIds = $command->getProductIds();
        $pageIndex = $command->getPageIndex() ?? 0;
        $pageSize = $command->getPageSize() ?? self::DEFAULT_PAGE_SIZE;

        return $productIds
            ? $this->getProductsByIds($productIds, $shopId, $pageIndex, $pageSize)
            : $this->getAllProducts($shopId, $pageIndex, $pageSize);
    }

    private function getProductsByIds(array $productIds, int $shopId, int $pageIndex, int $pageSize): Products
    {
        // filter out invalid reference IDs
        $referenceIds = array_filter(array_map([ReferenceId::class, 'fromString'], $productIds));
        natsort($referenceIds);

        $offset = $pageIndex * $pageSize;

        $totalCount = $this->repository->countBy($shopId, $referenceIds);
        $products = 0 !== $totalCount && $offset < $totalCount
            ? $this->repository->findBy($shopId, $pageSize, $offset, $referenceIds)
            : [];

        $responseProducts = array_map([$this, 'mapProductData'], $products);

        if ($totalCount === \count($referenceIds)) {
            return new Products($responseProducts, $totalCount, $pageIndex, $pageSize);
        }

        $knownIdsMap = array_flip(array_map(static function (IdentifiableProduct $product): string {
            return $product->getId();
        }, $responseProducts));

        $missingIds = array_filter($referenceIds, static function (ReferenceId $referenceId) use ($knownIdsMap): bool {
            return !isset($knownIdsMap[(string) $referenceId]);
        });

        $importableIds = $this->getImportableProductIds($shopId, $missingIds);

        return $this->appendApiProductData($responseProducts, $totalCount, $importableIds, $pageIndex, $pageSize);
    }

    private function getAllProducts(int $shopId, int $pageIndex, int $pageSize): Products
    {
        $offset = $pageIndex * $pageSize;

        $totalCount = $this->repository->countBy($shopId);
        $products = 0 !== $totalCount && $offset < $totalCount
            ? $this->repository->findBy($shopId, $pageSize, $offset)
            : [];

        $responseProducts = array_map([$this->dataMapper, 'map'], $products);
        $importableIds = $this->getImportableProductIds($shopId);

        return $this->appendApiProductData($responseProducts, $totalCount, $importableIds, $pageIndex, $pageSize);
    }

    private function appendApiProductData(array $responseProducts, int $totalCount, array $importableIds, int $pageIndex, int $pageSize): Products
    {
        if ([] === $importableIds) {
            return new Products($responseProducts, $totalCount, $pageIndex, $pageSize);
        }

        $offset = $pageIndex * $pageSize;
        $totalCount += \count($importableIds);

        $count = \count($responseProducts);

        if ($count === $pageSize || $offset > $totalCount) {
            return new Products($responseProducts, $totalCount, $pageIndex, $pageSize);
        }

        $productIds = \array_slice($importableIds, $offset - $count, $pageSize - $count);
        $apiProducts = $this->fetchApiProducts($productIds);

        $responseProducts = array_merge(
            $responseProducts,
            array_map([$this, 'mapApiProductData'], $apiProducts)
        );

        return new Products($responseProducts, $totalCount, $pageIndex, $pageSize);
    }

    private function mapProductData(HotProduct $product): IdentifiableProduct
    {
        return $this->dataMapper
            ->map($product)
            ->asIdentifiable((string) $product->getReferenceId());
    }

    private function mapApiProductData(Product $apiProduct): IdentifiableProduct
    {
        $referenceId = ReferenceId::fromString($apiProduct->getId());
        \assert(null !== $referenceId);

        $availability = $apiProduct->getAvailability() ?? new ProductAvailability();
        $product = new HotProduct(
            $referenceId->getProductId(),
            $referenceId->getCombinationId(),
            null,
            $availability->getStartDate(),
            $availability->getEndDate()
        );

        return $this->dataMapper
            ->map($product)
            ->asIdentifiable($apiProduct->getId());
    }

    /**
     * @param string[] $productIds
     *
     * @return Product[]
     */
    private function fetchApiProducts(array $productIds): array
    {
        $result = [];

        foreach ($productIds as $key => $productId) {
            if (!isset($this->apiProducts[$productId])) {
                continue;
            }

            unset($productIds[$key]);
            $result[] = $this->apiProducts[$productId];
        }

        if ([] === $productIds) {
            return $result;
        }

        $apiProducts = $this->client->getProducts($productIds);

        return array_merge($result, iterator_to_array($apiProducts));
    }

    /**
     * @param ReferenceId[] $referenceIds
     *
     * @return string[]
     */
    private function getImportableProductIds(int $shopId, array $referenceIds = []): array
    {
        $idsMap = $this->cache->get(self::IMPORTABLE_CACHE_KEY);
        $referenceIds = array_map(static function (ReferenceId $referenceId): string {
            return (string) $referenceId;
        }, $referenceIds);

        if (null === $idsMap || [] !== array_diff($referenceIds, $this->getNormalizedIds($idsMap))) {
            $idsMap = $this->createImportableProductIdsMap($shopId);

            $this->cache->set(self::IMPORTABLE_CACHE_KEY, $idsMap, self::CACHE_TTL);
        }

        $importableIds = array_keys(array_filter($idsMap));

        if ([] === $referenceIds) {
            return $importableIds;
        }

        return array_intersect($importableIds, $referenceIds);
    }

    private function getNormalizedIds(array $idsMap): array
    {
        $ids = array_keys($idsMap);

        return array_map([ReferenceId::class, 'fromString'], $ids);
    }

    /**
     * @return array<string, bool>
     */
    private function createImportableProductIdsMap(int $shopId): array
    {
        $map = [];

        foreach ($this->client->getProducts() as $apiProduct) {
            $productId = $apiProduct->getId();
            $map[$productId] = $this->isImportable($productId, $shopId);
            $this->apiProducts[$productId] = $apiProduct;
        }

        return $map;
    }

    private function isImportable(string $productId, int $shopId): bool
    {
        if (null === $referenceId = ReferenceId::fromString($productId)) {
            return false;
        }

        if ($referenceId->hasCustomization()) {
            return false;
        }

        if (null !== $this->repository->findOneByReferenceId($productId)) {
            return false;
        }

        $product = $this->productRepository->findWithCombination(
            $referenceId->getProductId(),
            $referenceId->getCombinationId(),
            null,
            $shopId,
            true
        );

        return null !== $product;
    }
}
