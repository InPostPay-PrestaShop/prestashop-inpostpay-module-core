<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use Symfony\Component\HttpFoundation\Request;

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
     * @param array{object?: \Cart, request?: Request} $parameters
     */
    public function execute(array $parameters): void
    {
        $cart = $parameters['object'] ?? null;

        if (!$cart instanceof \Cart) {
            throw InvalidHookParamException::unexpectedType('object', $cart, \Cart::class);
        }

        if (0 >= (int) $cart->id) {
            /* @see \Context::updateCustomer() before PS 8.0 might attempt to execute updates of carts that have not been persisted */
            return;
        }

        if ($this->context->controller instanceof \ModuleFrontControllerCore && $this->module === $this->context->controller->module) {
            return;
        }

        $request = $parameters['request'] ?? null;
        if (null !== $request && !$request instanceof Request) {
            throw InvalidHookParamException::unexpectedType('request', $request, Request::class . '|null');
        }

        $this->dispatcher->dispatch(new CartUpdatedEvent($cart, $request));
    }
}
