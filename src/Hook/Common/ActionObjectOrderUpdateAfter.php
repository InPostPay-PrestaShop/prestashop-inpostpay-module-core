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

    /**
     * @var \Context
     */
    private $context;

    public function __construct(EventDispatcherInterface $dispatcher, ?\Context $context = null)
    {
        $this->dispatcher = $dispatcher;
        $this->context = $context ?? \Context::getContext();
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public function execute(array $parameters): void
    {
        $order = $parameters['object'] ?? null;

        if (!$order instanceof \Order) {
            throw new \InvalidArgumentException(sprintf('Expected parameter "object" to be an instance of "%s", "%s" given.', \Order::class, get_debug_type($order)));
        }

        $controller = $this->context->controller;
        if ($controller instanceof \ModuleFrontControllerCore && 'inpostizi' === $controller->module->name) {
            return;
        }

        $this->dispatcher->dispatch(new OrderEvent($order), OrderEvent::UPDATED);
    }
}
