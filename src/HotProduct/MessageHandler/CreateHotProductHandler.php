<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\MessageHandler;

use izi\prestashop\BasketApp\Product\Exception\ProductExistsException;
use izi\prestashop\BasketApp\Product\ProductsApiClientInterface;
use izi\prestashop\BasketApp\Product\Request\CreateProductsRequest;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\HotProduct\Exception\HotProductExistsException;
use izi\prestashop\HotProduct\HotProduct;
use izi\prestashop\HotProduct\HotProductDataMapperInterface;
use izi\prestashop\HotProduct\HotProductRepositoryInterface;
use izi\prestashop\HotProduct\HotProductValidator;
use izi\prestashop\HotProduct\Message\CreateHotProductCommand;

final class CreateHotProductHandler implements CreateHotProductHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var HotProductRepositoryInterface
     */
    private $repository;

    /**
     * @var HotProductValidator
     */
    private $validator;

    /**
     * @var HotProductDataMapperInterface
     */
    private $dataMapper;

    /**
     * @var ProductsApiClientInterface
     */
    private $client;

    public function __construct(HotProductRepositoryInterface $repository, HotProductValidator $validator, HotProductDataMapperInterface $dataMapper, ProductsApiClientInterface $client)
    {
        $this->repository = $repository;
        $this->validator = $validator;
        $this->dataMapper = $dataMapper;
        $this->client = $client;
    }

    public function __invoke(CreateHotProductCommand $command): HotProduct
    {
        $product = new HotProduct(
            $command->getProductId(),
            $command->getCombinationId(),
            $command->getShopId(),
            $command->getAvailableFrom(),
            $command->getAvailableTo()
        );

        $this->validator->validate($product);

        $referenceId = (string) $product->getReferenceId();

        if (null !== $this->repository->findOneByReferenceId($referenceId, $product->getShopId())) {
            throw new HotProductExistsException('Hot product already exists');
        }

        $payload = $this->dataMapper->map($product)->asIdentifiable($referenceId);

        try {
            $this->client->createProducts(new CreateProductsRequest([$payload]));
        } catch (ProductExistsException $e) {
            // ignore and save data to the database
        }

        $this->repository->add($product);

        return $product;
    }
}
