<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\EventListener;

use izi\prestashop\Hook\Front\Event\RenderHeaderEvent;
use izi\prestashop\InPostDiscount\DiscountHandlerInterface;
use izi\prestashop\InPostDiscount\DiscountInterface;
use izi\prestashop\InPostDiscount\DiscountRepositoryInterface;
use izi\prestashop\InPostDiscount\Event\DiscountAppliedEvent;
use izi\prestashop\MerchantApi\Event\CreateOrderExceptionEvent;
use izi\prestashop\MerchantApi\Event\GetBasketRequestEvent;
use izi\prestashop\MerchantApi\Event\OrderCreatedEvent;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Removes InPost discounts from the cart if the order could not be created.
 */
final class ClearDiscountsListener implements EventSubscriberInterface
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var DiscountRepositoryInterface
     */
    private $repository;

    /**
     * @var DiscountHandlerInterface
     */
    private $handler;

    /**
     * @var ObjectRepositoryInterface<\Order>
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DiscountInterface[]
     */
    private $discounts = [];

    /**
     * @param ObjectRepositoryInterface<\Order> $orderRepository
     */
    public function __construct(\Context $context, DiscountRepositoryInterface $repository, DiscountHandlerInterface $handler, ObjectRepositoryInterface $orderRepository, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->repository = $repository;
        $this->handler = $handler;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DiscountAppliedEvent::class => 'onDiscountApplied',
            OrderCreatedEvent::class => 'onOrderCreated',
            CreateOrderExceptionEvent::class => 'onOrderException',
            GetBasketRequestEvent::class => 'onBasketRequest',
            RenderHeaderEvent::class => 'onFrontOfficeRequest',
        ];
    }

    public function onDiscountApplied(DiscountAppliedEvent $event): void
    {
        $this->discounts[] = $event->getDiscount();
    }

    public function onOrderCreated(OrderCreatedEvent $event): void
    {
        $this->discounts = [];
    }

    public function onOrderException(CreateOrderExceptionEvent $event): void
    {
        if ([] === $this->discounts) {
            return;
        }

        /** @var \Cart $cart */
        $cart = $event->getSession()->getBasket()->getEntity();

        if (!$this->hasOrder((int) $cart->id)) {
            $this->removeDiscounts($cart, $this->discounts);
        }

        $this->discounts = [];
    }

    public function onBasketRequest(GetBasketRequestEvent $event): void
    {
        $cartId = (int) $event->getSession()->getBasket()->getId();

        if ([] === $discounts = $this->repository->findByCartId($cartId)) {
            return;
        }

        if ($this->hasOrder($cartId)) {
            return;
        }

        $this->removeDiscounts($this->context->cart, $discounts);
    }

    public function onFrontOfficeRequest(): void
    {
        if (0 >= $cartId = (int) $this->context->cart->id) {
            return;
        }

        if ([] === $discounts = $this->repository->findByCartId($cartId)) {
            return;
        }

        if ($this->hasOrder($cartId)) {
            return;
        }

        $this->removeDiscounts($this->context->cart, $discounts);
    }

    private function hasOrder(int $cartId): bool
    {
        return 0 < $cartId && null !== $this->orderRepository->findOneBy(['id_cart' => $cartId]);
    }

    /**
     * @param DiscountInterface[] $discounts
     */
    private function removeDiscounts(\Cart $cart, array $discounts): void
    {
        foreach ($discounts as $discount) {
            try {
                $this->handler->remove($cart, $discount);
            } catch (\Throwable $e) {
                $this->logger->error('Could not remove discount "{type}" from cart #{cartId}.', [
                    'type' => $discount->getType(),
                    'cartId' => $cart->id,
                    'exception' => $e,
                ]);
            }
        }
    }
}
