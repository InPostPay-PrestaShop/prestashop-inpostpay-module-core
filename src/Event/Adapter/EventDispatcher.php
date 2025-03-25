<?php

declare(strict_types=1);

namespace izi\prestashop\Event\Adapter;

use izi\prestashop\Event\Event;
use izi\prestashop\Event\EventDispatcherInterface as ModuleEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;

/**
 * @mixin EventDispatcherInterface
 */
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

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * Forwards calls to the inner dispatcher.
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->dispatcher->$name(...$arguments);
    }
}
