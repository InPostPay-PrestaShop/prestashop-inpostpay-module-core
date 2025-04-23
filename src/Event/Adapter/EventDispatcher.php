<?php

declare(strict_types=1);

namespace izi\prestashop\Event\Adapter;

use izi\prestashop\Event\Event;
use izi\prestashop\Event\EventDispatcherInterface as ModuleEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

final class EventDispatcher implements ModuleEventDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var bool
     */
    private $isLegacyDispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->isLegacyDispatcher = !$this->dispatcher instanceof ContractsEventDispatcherInterface;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(Event $event, ?string $eventName = null): Event
    {
        $eventName = $eventName ?? get_class($event);

        return $this->isLegacyDispatcher
            ? $this->dispatcher->dispatch($eventName, $event)
            : $this->dispatcher->dispatch($event, $eventName);
    }
}
