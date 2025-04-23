<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\EventListener;

use izi\prestashop\MerchantApi\Event\CartUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UpdateCartRulesListener implements EventSubscriberInterface
{
    /**
     * @var \Context
     */
    private $context;

    public function __construct(\Context $context)
    {
        $this->context = $context;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartUpdatedEvent::class => 'onCartUpdated',
        ];
    }

    public function onCartUpdated(CartUpdatedEvent $event): void
    {
        $context = clone $this->context;
        $context->cart = $event->getCart();

        \CartRule::autoAddToCart($context);
        \CartRule::autoRemoveFromCart($context);
    }
}
