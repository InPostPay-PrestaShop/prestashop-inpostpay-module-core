<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\Builder\Basket\BasketBuilderFactoryInterface;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\MerchantApi\Command\GetBasketCommand;
use izi\prestashop\MerchantApi\Event\GetBasketRequestEvent;
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

    /**
     * @var EventDispatcherInterface|null
     */
    private $eventDispatcher;

    public function __construct(BasketSessionRepositoryInterface $repository, BasketBuilderFactoryInterface $builderFactory, ?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->repository = $repository;
        $this->builderFactory = $builderFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getHandledCommandClass(): string
    {
        return GetBasketCommand::class;
    }

    public function __invoke(GetBasketCommand $command): Basket
    {
        if (null === $session = $this->repository->findByBasketId($command->getBasketId())) {
            throw BasketNotFoundException::create();
        }

        if (null !== $this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new GetBasketRequestEvent($session));
        }

        return $this->builderFactory
            ->createResponseBuilder($session->getBasket(), $session->getShopId())
            ->build();
    }
}
