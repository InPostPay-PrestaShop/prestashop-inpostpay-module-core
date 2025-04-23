<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\MessageHandler;

use izi\prestashop\BasketApp\Product\Exception\ProductNotFoundException;
use izi\prestashop\BasketApp\Product\ProductsApiClientInterface;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\HotProduct\HotProductRepositoryInterface;
use izi\prestashop\HotProduct\Message\DeleteRemoteProductCommand;

final class DeleteRemoteProductHandler implements DeleteRemoteProductHandlerInterface
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

    public function __invoke(DeleteRemoteProductCommand $command): void
    {
        $product = $this->repository->findOneByReferenceId($command->getId(), $command->getShopId());

        try {
            $this->client->deleteProduct($command->getId());
        } catch (ProductNotFoundException $e) {
            // ignore silently
        }

        if (null !== $product) {
            $this->repository->remove($product);
        }
    }
}
