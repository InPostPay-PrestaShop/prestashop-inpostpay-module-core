<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\MessageHandler;

use izi\prestashop\BasketApp\Product\ProductsApiClientInterface;
use izi\prestashop\BasketApp\Product\Response\Product;
use izi\prestashop\Common\HotProduct\ProductAvailability;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\HotProduct\Exception\HotProductExistsException;
use izi\prestashop\HotProduct\Exception\HotProductNotFoundException;
use izi\prestashop\HotProduct\Exception\InvalidProductDataException;
use izi\prestashop\HotProduct\HotProduct;
use izi\prestashop\HotProduct\HotProductDataMapperInterface;
use izi\prestashop\HotProduct\HotProductRepositoryInterface;
use izi\prestashop\HotProduct\HotProductValidator;
use izi\prestashop\HotProduct\Message\ImportHotProductCommand;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use izi\prestashop\Product\ReferenceId;

final class ImportHotProductHandler implements ImportHotProductHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var HotProductRepositoryInterface
     */
    private $repository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ProductsApiClientInterface
     */
    private $client;

    /**
     * @var HotProductDataMapperInterface
     */
    private $dataMapper;

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(HotProductRepositoryInterface $repository, ObjectRepositoryInterface $productRepository, ProductsApiClientInterface $client, HotProductDataMapperInterface $dataMapper)
    {
        $this->repository = $repository;
        $this->productRepository = $productRepository;
        $this->client = $client;
        $this->dataMapper = $dataMapper;
    }

    public function __invoke(ImportHotProductCommand $command): HotProduct
    {
        $referenceId = ReferenceId::fromString($command->getId());

        if (null === $referenceId || $referenceId->hasCustomization()) {
            throw new InvalidProductDataException('Invalid product reference ID.');
        }

        $productWithCombination = $this->productRepository->findWithCombination(
            $productId = $referenceId->getProductId(),
            $referenceId->getCombinationId(),
            null,
            $shopId = $command->getShopId(),
            true
        );

        if (null === $productWithCombination) {
            throw new InvalidProductDataException('Product or combination does not exist.');
        }

        if (!HotProductValidator::isAvailableForOrder($productWithCombination->getProduct())) {
            throw new InvalidProductDataException('Product is inactive or not available for order.');
        }

        $combination = $productWithCombination->getCombination();
        $combinationId = $combination ? (int) $combination->id : null;

        if (null !== $this->repository->findOneByProductId($productId, $combinationId, $shopId)) {
            throw new HotProductExistsException('Hot product already exists.');
        }

        $apiProduct = $this->fetchProduct((string) $referenceId);
        $availability = $apiProduct->getAvailability() ?? new ProductAvailability();

        $this->repository->add($hotProduct = new HotProduct(
            $productId,
            $combinationId,
            $shopId,
            $availability->getStartDate(),
            $availability->getEndDate()
        ));

        // synchronize product data
        $payload = $this->dataMapper->map($hotProduct);
        $this->client->updateProduct((string) $referenceId, $payload);

        return $hotProduct;
    }

    private function fetchProduct(string $id): Product
    {
        $products = iterator_to_array($this->client->getProducts([$id]));

        if ([] === $products) {
            throw new HotProductNotFoundException('API product not found.');
        }

        return $products[0];
    }
}
