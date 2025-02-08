<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Basket\V2\BasketsApiClientInterface;
use izi\prestashop\Command\GetBasketBindingKeyCommand;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Handler\Result\BasketBindingKey;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class GetBasketBindingKeyHandler implements GetBasketBindingKeyHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var BasketsApiClientInterface
     */
    private $client;

    /**
     * @param BasketSessionRepositoryInterface<BasketSession> $repository
     */
    public function __construct(BasketSessionRepositoryInterface $repository, BasketsApiClientInterface $client)
    {
        $this->repository = $repository;
        $this->client = $client;
    }

    public function __invoke(GetBasketBindingKeyCommand $command): BasketBindingKey
    {
        $basket = $command->getBasket();

        if (null === $session = $this->repository->findByEntityId($basket->getId())) {
            $session = $this->createNewSession($basket);
        }

        if (!is_callable([$session, 'getBindingApiKey'])) {
            throw new \LogicException('Basket session does not support storing binding keys.');
        }

        if (!$command->isRefresh() && null !== $key = $session->getBindingApiKey()) {
            return new BasketBindingKey($session->getBasketId(), $key);
        }

        if (null !== $session->getOrderId()) {
            throw new \DomainException(sprintf('Basket "%s" was already finalized.', $basket->getId()));
        }

        $key = $this->client->initializeBasketBinding($session->getBasketId())->getBindingKey();

        $session->setBindingApiKey($key);
        $this->repository->persist($session);

        return new BasketBindingKey($session->getBasketId(), $key);
    }

    private function createNewSession(BasketInterface $basket): BasketSessionInterface
    {
        $session = $this->repository->createNewSession($basket);
        $this->repository->persist($session);

        return $session;
    }
}
