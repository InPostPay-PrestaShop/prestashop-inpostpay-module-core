<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;

final class ActionCartUpdateAfter implements HookInterface
{
    public const HOOK_NAME = 'actionObjectCartUpdateAfter';

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(\Module $module, \Context $context, EventDispatcherInterface $dispatcher)
    {
        $this->module = $module;
        $this->context = $context;
        $this->dispatcher = $dispatcher;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{object?: \Cart} $parameters
     */
    public function execute(array $parameters): void
    {
        $cart = $parameters['object'] ?? null;

        if (!$cart instanceof \Cart) {
            throw InvalidHookParamException::unexpectedType('object', $cart, \Cart::class);
        }

        if ($this->context->controller instanceof \ModuleFrontControllerCore && $this->module === $this->context->controller->module) {
            return;
        }

        $this->dispatcher->dispatch(new CartUpdatedEvent($cart));
    }
}
