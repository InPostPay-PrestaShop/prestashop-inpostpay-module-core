<?php

declare(strict_types=1);

namespace izi\prestashop\Event;

use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;
use izi\prestashop\EventListener\CartListener;
use izi\prestashop\EventListener\OrderListener;
use izi\prestashop\EventListener\ShipmentListener;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final class EventDispatcherFactory implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices(): array
    {
        return [
            CartListener::class,
            OrderListener::class,
            ShipmentListener::class,
        ];
    }

    public function create(): EventDispatcherInterface
    {
        $dispatcher = new EventDispatcher();

        foreach (self::getSubscribedServices() as $className) {
            $this->addListeners($dispatcher, $className);
        }

        return $dispatcher;
    }

    /**
     * @param class-string<EventSubscriberInterface> $className
     */
    private function addListeners(EventDispatcherInterface $dispatcher, string $className): void
    {
        foreach ($className::getSubscribedEvents() as $eventName => $parameters) {
            foreach ($this->normalizeListeners($parameters) as $listener) {
                [$method, $priority] = $listener;

                $dispatcher->addListener($eventName, function ($event) use ($className, $method) {
                    return $this->locator->get($className)->{$method}($event);
                }, $priority);
            }
        }
    }

    private function normalizeListeners($parameters): array
    {
        if (is_string($parameters)) {
            return [[$parameters, 0]];
        }

        if (is_string($parameters[0])) {
            return [[$parameters[0], $parameters[1] ?? 0]];
        }

        return array_map(static function ($listener) {
            return [$listener[0], $listener[1] ?? 0];
        }, $parameters);
    }
}
