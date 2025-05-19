<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Basket\BasketsApiClientInterface;
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
     * @var \Context
     */
    private $context;

    /**
     * @param BasketSessionRepositoryInterface<BasketSession> $repository
     */
    public function __construct(BasketSessionRepositoryInterface $repository, BasketsApiClientInterface $client, ?\Context $context = null)
    {
        $this->context = $context ?? \Context::getContext();
        $this->repository = $repository;
        $this->client = $client;
    }

    public function __invoke(GetBasketBindingKeyCommand $command): BasketBindingKey
    {
        $basket = $command->getBasket();

        if (null === $session = $this->repository->findByEntityId($basket->getId())) {
            $session = $this->createNewSession($basket);
        }

        if (!$command->isRefresh() && null !== $key = $session->getBindingApiKey()) {
            return new BasketBindingKey($session->getBasketId(), $key);
        }

        if (BasketSession::isFinalized($session)) {
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
        $session->setShopId((int) $this->context->shop->id);
        $this->repository->persist($session);

        return $session;
    }
}
