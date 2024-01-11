<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\Builder\Basket\BasketBuilderFactoryInterface;
use izi\prestashop\MerchantApi\Command\ConfirmBasketBindingCommand;
use izi\prestashop\MerchantApi\Exception\BasketNotFoundException;
use izi\prestashop\MerchantApi\Exception\OrderExistsException;
use izi\prestashop\MerchantApi\Model\Basket\Response\Basket;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class ConfirmBasketBindingHandler implements ConfirmBasketBindingHandlerInterface
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

    public static function getHandledCommandClass(): string
    {
        return ConfirmBasketBindingCommand::class;
    }

    public function __invoke(ConfirmBasketBindingCommand $command): Basket
    {
        if (null === $session = $this->repository->findByBasketId($command->getBasketId())) {
            throw BasketNotFoundException::create();
        }

        if (null !== $session->getOrderId()) {
            throw OrderExistsException::create();
        }

        $session->setBindingConfirmation($command->getConfirmation());
        $this->repository->persist($session);

        return $this->builderFactory
            ->createResponseBuilder($session->getBasket())
            ->build();
    }
}
