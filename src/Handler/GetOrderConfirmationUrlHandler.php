<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\GetOrderConfirmationUrlCommand;
use izi\prestashop\Order\ContextCustomerUpdater;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class GetOrderConfirmationUrlHandler implements GetOrderConfirmationUrlHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var ContextCustomerUpdater
     */
    private $contextUpdater;

    public function __construct(BasketSessionRepositoryInterface $repository, ContextCustomerUpdater $contextUpdater)
    {
        $this->repository = $repository;
        $this->contextUpdater = $contextUpdater;
    }

    public function __invoke(GetOrderConfirmationUrlCommand $command): string
    {
        if (null === $session = $this->repository->findByBasketId($command->getBasketId())) {
            throw new \DomainException(\sprintf('Basket "%s" does not exist.', $command->getBasketId()));
        }

        if (null === $orderId = $session->getOrderId()) {
            throw new \DomainException(\sprintf('Basket "%s" was not finalized.', $session->getBasketId()));
        }

        $session->redirect();
        $this->repository->persist($session);

        $this->contextUpdater->updateCustomer((int) $orderId);

        return $session->getOrderConfirmationUrl();
    }
}
