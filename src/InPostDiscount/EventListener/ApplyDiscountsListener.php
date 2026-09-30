<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\EventListener;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\InPostDiscount\DiscountHandlerInterface;
use izi\prestashop\InPostDiscount\Event\DiscountAppliedEvent;
use izi\prestashop\InPostDiscount\Exception\UnsupportedTypeException;
use izi\prestashop\MerchantApi\Event\CreateOrderRequestEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ApplyDiscountsListener implements EventSubscriberInterface
{
    /**
     * @var DiscountHandlerInterface
     */
    private $handler;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(DiscountHandlerInterface $handler, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        $this->handler = $handler;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CreateOrderRequestEvent::class => 'applyDiscounts',
        ];
    }

    public function applyDiscounts(CreateOrderRequestEvent $event): void
    {
        $cart = $event->getSession()->getBasket()->getEntity();

        if (!$cart instanceof \Cart) {
            return;
        }

        foreach ($event->getRequest()->getOrderDetails()->getInpostDiscounts() as $requestDiscount) {
            try {
                $discount = $this->handler->apply($cart, $requestDiscount);
            } catch (UnsupportedTypeException $e) {
                $this->logger->warning('Unsupported InPost discount type "{type}".', [
                    'type' => $requestDiscount->getType(),
                ]);

                continue;
            }

            if (null === $discount) {
                continue;
            }

            $this->eventDispatcher->dispatch(new DiscountAppliedEvent($cart, $discount));
        }
    }
}
