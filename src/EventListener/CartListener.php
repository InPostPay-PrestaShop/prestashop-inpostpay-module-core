<?php

declare(strict_types=1);

namespace izi\prestashop\EventListener;

use izi\prestashop\Command\UpdateBasketCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Entities\Cart;
use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CartListener implements EventSubscriberInterface
{
    /**
     * @var ApiConfigurationInterface
     */
    private $configuration;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var BasketSessionRepositoryInterface<BasketSession>
     */
    private $sessionRepository;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array<int, int>
     */
    private $updatedCartIds = [];

    /**
     * @param BasketSessionRepositoryInterface<BasketSession> $sessionRepository
     */
    public function __construct(ApiConfigurationInterface $configuration, \Context $context, BasketSessionRepositoryInterface $sessionRepository, CommandBusInterface $bus, LoggerInterface $logger)
    {
        $this->configuration = $configuration;
        $this->context = $context;
        $this->sessionRepository = $sessionRepository;
        $this->bus = $bus;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartUpdatedEvent::class => 'onCartUpdated',
        ];
    }

    public function onCartUpdated(CartUpdatedEvent $event): void
    {
        if (null === $this->configuration->getClientCredentials()) {
            return;
        }

        $cart = $event->getCart();

        if (0 >= $cartId = (int) $cart->id) {
            return;
        }

        if ([] === $this->updatedCartIds) {
            register_shutdown_function(function () {
                $this->updateBaskets();
            });
        }

        $this->updatedCartIds[$cartId] = $cartId;
    }

    private function updateBaskets(): void
    {
        foreach ($this->updatedCartIds as $cartId) {
            try {
                $this->handleCurrentCartChange($cartId);
                $command = new UpdateBasketCommand($cartId);

                $this->bus->handle($command);
            } catch (\Throwable $throwable) {
                $this->logger->critical('Could not send updated cart #{cartId} data: {error}', [
                    'cartId' => $cartId,
                    'error' => $throwable,
                ]);
            }
        }
    }

    private function handleCurrentCartChange(int $cartId): void
    {
        if (null !== $this->sessionRepository->findByEntityId($cartId)) {
            return;
        }

        if (!$this->context->controller instanceof \FrontControllerCore || $cartId !== (int) $this->context->cart->id) {
            return;
        }

        if (null === $currentBasketId = $this->context->cookie->inpostizi_basket_id ?? null) {
            return;
        }

        if (null === $session = $this->sessionRepository->findByBasketId($currentBasketId)) {
            return;
        }

        // the user changed his current cart (e.g. using the reorder option)
        $session->switchBasket(new Cart($this->context->cart));
        $this->sessionRepository->persist($session);
    }
}
