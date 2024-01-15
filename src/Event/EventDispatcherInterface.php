<?php

declare(strict_types=1);

namespace izi\prestashop\Event;

interface EventDispatcherInterface
{
    /**
     * @template T of Event $event
     *
     * @param T $event
     * @param string|null $eventName if null, the supplied event's class name will be used
     *
     * @return T the passed event
     */
    public function dispatch(Event $event, string $eventName = null): Event;
}
