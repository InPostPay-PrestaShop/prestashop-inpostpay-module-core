<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Event\OrderEvent;
use izi\prestashop\Hook\HookInterface;

final class ActionObjectOrderUpdateAfter implements HookInterface
{
    public const HOOK_NAME = 'actionObjectOrderUpdateAfter';

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }


    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public function execute(array $parameters)
    {
        $orderObj = $parameters['object'] ?? null;

        if (!$orderObj instanceof \Order) {
            throw new \InvalidArgumentException(sprintf('Parameter "object" expected to be an instance of "%s", "%s" given.', \Order::class, get_debug_type($orderObj)));
        }

        $this->dispatcher->dispatch(new OrderEvent($orderObj), OrderEvent::UPDATED);
    }

}
