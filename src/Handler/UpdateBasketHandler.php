<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Basket\BasketsApiClientInterface;
use izi\prestashop\BasketApp\Basket\V2;
use izi\prestashop\BasketApp\Exception\BasketExpiredException;
use izi\prestashop\BasketApp\Exception\BasketNotBoundException;
use izi\prestashop\BasketApp\Exception\BasketNotFoundException;
use izi\prestashop\Builder\Basket\BasketBuilderFactoryInterface;
use izi\prestashop\Command\UpdateBasketCommand;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use Psr\Log\LoggerInterface;

final class UpdateBasketHandler implements UpdateBasketHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $sessionRepository;

    /**
     * @var BasketBuilderFactoryInterface
     */
    private $builderFactory;

    /**
     * @var BasketsApiClientInterface
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(BasketSessionRepositoryInterface $sessionRepository, BasketBuilderFactoryInterface $builderFactory, BasketsApiClientInterface $client, LoggerInterface $logger)
    {
        if (!$client instanceof V2\BasketsApiClientInterface) {
            @trigger_error(sprintf('Passing a $client that does not implement "%s" to "%s::__construct()" is deprecated.', V2\BasketsApiClientInterface::class, self::class));
        }

        $this->sessionRepository = $sessionRepository;
        $this->builderFactory = $builderFactory;
        $this->client = $client;
        $this->logger = $logger;
    }

    public function __invoke(UpdateBasketCommand $command)
    {
        $session = $this->sessionRepository->findByEntityId($cartId = $command->getBasketId());

        if (null === $session || !$session->isBasketBound() || null !== $session->getOrderId()) {
            return;
        }

        $basket = $this->builderFactory
            ->createRequestBuilder($session->getBasket())
            ->build();

        try {
            $this->client->upsertBasket($session->getBasketId(), $basket);
        } catch (BasketNotFoundException|BasketNotBoundException|BasketExpiredException $e) {
            $this->logger->warning('API error "{code}" for cart #{cartId} update, resetting binding status', [
                'code' => $e->getError()->getCode(),
                'cartId' => $cartId,
            ]);

            $session->unbind();
            $this->sessionRepository->persist($session);
        }
    }
}
