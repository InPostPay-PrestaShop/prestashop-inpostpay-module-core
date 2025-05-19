<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\Builder\Basket\BasketBuilderFactoryInterface;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\MerchantApi\Command\UpdateBasketCommand;
use izi\prestashop\MerchantApi\Exception\BasketNotFoundException;
use izi\prestashop\MerchantApi\Exception\OrderExistsException;
use izi\prestashop\MerchantApi\Handler\Basket\BasketEventHandlerInterface;
use izi\prestashop\MerchantApi\Model\Basket\Response\Basket;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class UpdateBasketHandler implements UpdateBasketHandlerInterface
{
    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var BasketEventHandlerInterface
     */
    private $eventHandler;

    /**
     * @var BasketBuilderFactoryInterface
     */
    private $builderFactory;

    public function __construct(BasketSessionRepositoryInterface $repository, BasketEventHandlerInterface $eventHandler, BasketBuilderFactoryInterface $builderFactory)
    {
        $this->repository = $repository;
        $this->eventHandler = $eventHandler;
        $this->builderFactory = $builderFactory;
    }

    public static function getHandledCommandClass(): string
    {
        return UpdateBasketCommand::class;
    }

    public function __invoke(UpdateBasketCommand $command): Basket
    {
        if (null === $session = $this->repository->findByBasketId($command->getBasketId())) {
            throw BasketNotFoundException::create();
        }

        if (BasketSession::isFinalized($session)) {
            throw OrderExistsException::create();
        }

        $event = $command->getEvent();
        $notice = $this->eventHandler->handle($session->getBasket(), $event, $session->getShopId());

        $session->updatedBy($event);
        $this->repository->persist($session);

        return $this->builderFactory
            ->createResponseBuilder($session->getBasket(), $session->getShopId())
            ->setNotice($notice)
            ->build();
    }
}
