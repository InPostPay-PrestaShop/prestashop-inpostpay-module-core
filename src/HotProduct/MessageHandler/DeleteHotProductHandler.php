<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\MessageHandler;

use izi\prestashop\BasketApp\Product\Exception\ProductNotFoundException;
use izi\prestashop\BasketApp\Product\ProductsApiClientInterface;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\HotProduct\HotProductRepositoryInterface;
use izi\prestashop\HotProduct\Message\DeleteHotProductCommand;

final class DeleteHotProductHandler implements DeleteHotProductHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var HotProductRepositoryInterface
     */
    private $repository;

    /**
     * @var ProductsApiClientInterface
     */
    private $client;

    public function __construct(HotProductRepositoryInterface $repository, ProductsApiClientInterface $client)
    {
        $this->repository = $repository;
        $this->client = $client;
    }

    public function __invoke(DeleteHotProductCommand $command): void
    {
        if (null === $product = $this->repository->find($command->getId())) {
            return;
        }

        try {
            $this->client->deleteProduct((string) $product->getReferenceId());
        } catch (ProductNotFoundException $e) {
            // ignore and remove data from the database
        }

        $this->repository->remove($product);
    }
}
