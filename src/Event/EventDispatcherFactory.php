<?php

declare(strict_types=1);

namespace izi\prestashop\Event;

use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class EventDispatcherFactory
{
    public static function create(ServiceProviderInterface $locator): EventDispatcherInterface
    {
        $dispatcher = new EventDispatcher();

        foreach ($locator->getProvidedServices() as $id => $type) {
            self::registerServiceSubscriber($dispatcher, $locator, (string) $id, $type);
        }

        return $dispatcher;
    }

    private static function registerServiceSubscriber(EventDispatcher $dispatcher, ServiceProviderInterface $container, string $id, string $type): void
    {
        if ('?' === $type) {
            $class = $id;
        } else {
            $class = '?' === $type[0] ? substr($type, 1) : $type;
        }

        if (!is_subclass_of($class, EventSubscriberInterface::class)) {
            throw new InvalidArgumentException(\sprintf('Service "%s" must implement interface "%s".', $id, EventSubscriberInterface::class));
        }

        foreach (self::getSubscribedEvents($class) as $eventName => $listeners) {
            foreach (self::normalizeListeners($listeners) as $listener) {
                [$method, $priority] = $listener;

                $listener = static function ($event) use ($container, $id, $method) {
                    return $container->get($id)->{$method}($event);
                };

                $dispatcher->addListener($eventName, $listener, $priority);
            }
        }
    }

    /**
     * @param class-string<EventSubscriberInterface> $class
     */
    private static function getSubscribedEvents(string $class): array
    {
        /** @var iterable $subscribedEvents */
        $subscribedEvents = $class::getSubscribedEvents();

        return \is_array($subscribedEvents) ? $subscribedEvents : iterator_to_array($subscribedEvents);
    }

    /**
     * @param string|array{0: string, 1: int}|list<array{0: string, 1?: int}> $listeners
     *
     * @return list<array{0: string, 1: int}>
     */
    private static function normalizeListeners($listeners): array
    {
        if (\is_string($listeners)) {
            return [[$listeners, 0]];
        }

        if (\is_string($listeners[0])) {
            return [[$listeners[0], $listeners[1]]];
        }

        return array_map(static function ($listener) {
            return [$listener[0], $listener[1] ?? 0];
        }, $listeners);
    }
}
