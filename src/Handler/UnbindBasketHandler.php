<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Basket\BasketsApiClientInterface;
use izi\prestashop\BasketApp\Exception\BasketExpiredException;
use izi\prestashop\BasketApp\Exception\BasketNotBoundException;
use izi\prestashop\BasketApp\Exception\BasketNotFoundException;
use izi\prestashop\Command\UnbindBasketCommand;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class UnbindBasketHandler implements UnbindBasketHandlerInterface
{
    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var BasketsApiClientInterface
     */
    private $client;

    public function __construct(BasketSessionRepositoryInterface $repository, BasketsApiClientInterface $client)
    {
        $this->repository = $repository;
        $this->client = $client;
    }

    public static function getHandledCommandClass(): string
    {
        return UnbindBasketCommand::class;
    }

    public function __invoke(UnbindBasketCommand $command)
    {
        if (null === $session = $this->repository->findByEntityId($command->getBasketId())) {
            return;
        }

        try {
            $this->client->deleteBasketBinding($session->getBasketId(), $command->isOrderCompleted());
        } catch (BasketNotFoundException|BasketNotBoundException|BasketExpiredException $e) {
            // binding does not exist or will be deleted shortly
        }

        if (null === $session->getBindingConfirmation()) {
            return;
        }

        $session->unbind();
        $this->repository->persist($session);
    }
}
