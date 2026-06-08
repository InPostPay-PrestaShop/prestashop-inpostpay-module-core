<?php

declare(strict_types=1);

namespace izi\prestashop\EventListener;

use izi\prestashop\Cart\EventListener\SwitchBasketListener;
use izi\prestashop\Cart\EventListener\UpdateBasketListener;
use izi\prestashop\Cart\Storage\ChainBasketIdStorage;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Event\Adapter\EventDispatcher as EventDispatcherAdapter;
use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\TerminateEvent;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @deprecated use {@see SwitchBasketListener} and {@see UpdateBasketListener} instead
 */
final class CartListener implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $listeners;

    /**
     * @var EventDispatcherInterface|null
     */
    private $eventDispatcher;

    /**
     * @param BasketSessionRepositoryInterface<BasketSession> $sessionRepository
     */
    public function __construct(ApiConfigurationInterface $configuration, \Context $context, BasketSessionRepositoryInterface $sessionRepository, CommandBusInterface $bus, LoggerInterface $logger)
    {
        @trigger_error(\sprintf('Class "%s" is deprecated since version 3.3.0.', __CLASS__), \E_USER_DEPRECATED);

        $this->listeners = [
            new SwitchBasketListener($context, ChainBasketIdStorage::createDefault($context), $sessionRepository, $logger),
            new UpdateBasketListener($configuration, $bus, $logger),
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartUpdatedEvent::class => 'onCartUpdated',
        ];
    }

    public function onCartUpdated(CartUpdatedEvent $event/*, string $eventName, EventDispatcherInterface $dispatcher*/): void
    {
        $args = \func_get_args();
        $eventName = $args[1] ?? null;
        $dispatcher = $args[2] ?? null;

        if (!\is_string($eventName)) {
            $eventName = CartUpdatedEvent::class;
        }

        if (!$dispatcher instanceof EventDispatcherInterface) {
            $dispatcher = $this->getEventDispatcher();
        }

        /** @var SwitchBasketListener|UpdateBasketListener $listener */
        foreach ($this->listeners as $listener) {
            $listener->onCartUpdated($event, $eventName, $dispatcher);
        }
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        if (!isset($this->eventDispatcher)) {
            register_shutdown_function(function () {
                (new EventDispatcherAdapter($this->eventDispatcher))->dispatch(new TerminateEvent());
            });
        }

        return $this->eventDispatcher ?? $this->eventDispatcher = new EventDispatcher();
    }
}
