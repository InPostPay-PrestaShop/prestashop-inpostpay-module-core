<?php

declare(strict_types=1);

namespace izi\prestashop\Cart\EventListener;

use izi\prestashop\Cart\Storage\BasketIdStorageInterface;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Entities\Cart;
use izi\prestashop\Entities\SwitchableBasketSessionInterface;
use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\TerminateEvent;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Detects when the current customer cart was changed (e.g. via the reorder option)
 * and associates the active basket session with the new cart.
 */
final class SwitchBasketListener implements EventSubscriberInterface
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var BasketIdStorageInterface
     */
    private $basketIdStorage;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SwitchableBasketSessionInterface<Cart>|null
     */
    private $session;

    /**
     * @var array<int, int>
     */
    private $cartIds = [];

    /**
     * @param BasketSessionRepositoryInterface<BasketSession> $repository
     */
    public function __construct(\Context $context, BasketIdStorageInterface $basketIdStorage, BasketSessionRepositoryInterface $repository, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->basketIdStorage = $basketIdStorage;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartUpdatedEvent::class => ['onCartUpdated', 16],
        ];
    }

    public function onCartUpdated(CartUpdatedEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        if (0 >= $cartId = (int) $event->getCart()->id) {
            return;
        }

        if (isset($this->cartIds[$cartId])) {
            // the updated cart is already registered for an association check
            return;
        }

        if (!$this->context->controller instanceof \FrontControllerCore || null === $event->getRequest()) {
            // not a customer request context
            return;
        }

        if ($cartId === $this->getContextCartId()) {
            // the updated cart is the current cart
            return;
        }

        if (null === $this->session = $this->getCurrentSession()) {
            return;
        }

        if (null !== $this->repository->findByEntityId($cartId)) {
            // the updated cart is already associated with another basket session
            // TODO: update the current basket ID in the session storage?
            return;
        }

        if ([] === $this->cartIds) {
            $dispatcher->addListener(TerminateEvent::class, [$this, 'onTerminate'], 16);
        }

        $this->cartIds[$cartId] = $cartId;
    }

    public function onTerminate(TerminateEvent $event): void
    {
        if (null === $session = $this->session) {
            return;
        }

        $cartId = $this->getContextCartId();

        try {
            if (!isset($this->cartIds[(int) $cartId])) {
                return;
            }

            $session->switchBasket(new Cart($this->context->cart));
            $this->repository->persist($session);
        } catch (\Throwable $e) {
            $this->logger->error('Could not update cart association for basket session "{basketId}".', [
                'basketId' => $session->getBasketId(),
                'cartId' => $cartId,
                'exception' => $e,
            ]);
        } finally {
            $this->session = null;
            $this->cartIds = [];
        }
    }

    private function getCurrentSession(): ?SwitchableBasketSessionInterface
    {
        if (null === $basketId = $this->basketIdStorage->getBasketId()) {
            // no InPost Pay basket is associated with the current session
            return null;
        }

        if (null === $session = $this->repository->findByBasketId($basketId)) {
            $this->logger->error('Basket session "{basketId}" was not found.', [
                'basketId' => $basketId,
            ]);
            $this->basketIdStorage->setBasketId(null);

            return null;
        }

        if (null !== $orderId = $session->getOrderId()) {
            $this->logger->notice('Order #{orderId}: basket ID was not removed from the customer session.', [
                'orderId' => $orderId,
            ]);
            $this->basketIdStorage->setBasketId(null);

            return null;
        }

        return $session instanceof SwitchableBasketSessionInterface ? $session : null;
    }

    private function getContextCartId(): ?int
    {
        if (!isset($this->context->cart)) {
            return null;
        }

        return (int) $this->context->cart->id;
    }
}
