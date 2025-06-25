<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\MessageHandler;

use izi\prestashop\BasketApp\Product\Exception\ProductNotFoundException;
use izi\prestashop\BasketApp\Product\ProductsApiClientInterface;
use izi\prestashop\BasketApp\Product\Request\CreateProductsRequest;
use izi\prestashop\BasketApp\Product\Response\Status;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\HotProduct\Exception\HotProductNotFoundException;
use izi\prestashop\HotProduct\HotProductDataMapperInterface;
use izi\prestashop\HotProduct\HotProductRepositoryInterface;
use izi\prestashop\HotProduct\Message\UpdateHotProductCommand;

final class UpdateHotProductHandler implements UpdateHotProductHandlerInterface
{
    use CommandHandlerTrait;

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

    public function __construct(HotProductRepositoryInterface $repository, HotProductDataMapperInterface $dataMapper, ProductsApiClientInterface $client)
    {
        $this->repository = $repository;
        $this->dataMapper = $dataMapper;
        $this->client = $client;
    }

    public function __invoke(UpdateHotProductCommand $command): Status
    {
        if (null === $product = $this->repository->find($command->getId())) {
            throw new HotProductNotFoundException('Hot product not found.');
        }

        if ($command->updateAvailability()) {
            $product->setAvailability($command->getAvailableFrom(), $command->getAvailableTo());
        }

        $referenceId = (string) $product->getReferenceId();
        $payload = $this->dataMapper->map($product);

        try {
            $status = $this->client->updateProduct($referenceId, $payload)->getStatus();
        } catch (ProductNotFoundException $e) {
            if (!$command->createIfNotFound()) {
                throw $e;
            }

            $payload = $payload->asIdentifiable($referenceId);
            $this->client->createProducts(new CreateProductsRequest([$payload]));

            $status = Status::Inactive();
        }

        if ($command->updateAvailability()) {
            $this->repository->update($product);
        }

        return $status;
    }
}
