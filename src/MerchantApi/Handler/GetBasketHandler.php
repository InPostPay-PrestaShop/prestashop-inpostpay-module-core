<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\Builder\Basket\BasketBuilderFactoryInterface;
use izi\prestashop\MerchantApi\Command\GetBasketCommand;
use izi\prestashop\MerchantApi\Exception\BasketNotFoundException;
use izi\prestashop\MerchantApi\Model\Basket\Response\Basket;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class GetBasketHandler implements GetBasketHandlerInterface
{
    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var BasketBuilderFactoryInterface
     */
    private $builderFactory;

    public function __construct(BasketSessionRepositoryInterface $repository, BasketBuilderFactoryInterface $builderFactory)
    {
        $this->repository = $repository;
        $this->builderFactory = $builderFactory;
    }

    public function __invoke(GetBasketCommand $command): Basket
    {
        if (null === $session = $this->repository->findByBasketId($command->getBasketId())) {
            throw BasketNotFoundException::create();
        }

        return $this->builderFactory
            ->createResponseBuilder($session->getBasket())
            ->build();
    }
}
