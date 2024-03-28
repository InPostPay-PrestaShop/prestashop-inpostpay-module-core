<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\HookInterface;

final class ActionCartSave implements HookInterface
{
    public const HOOK_NAME = 'actionCartSave';

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
     * @param array{cart: \Cart} $parameters
     */
    public function execute(array $parameters): void
    {
        $cart = $parameters['cart'] ?? null;

        if (null === $cart && array_key_exists('cart', $parameters)) {
            return; // TODO: try to get cart from backtrace or use different hooks
        }

        if (!$cart instanceof \Cart) {
            throw new \InvalidArgumentException(sprintf('Parameter "cart" expected to be an instance of "%s", "%s" given.', \Cart::class, is_object($cart) ? get_class($cart) : gettype($cart)));
        }

        if ($this->context->controller instanceof \ModuleFrontControllerCore && $this->module === $this->context->controller->module) {
            return;
        }

        $this->dispatcher->dispatch(new CartUpdatedEvent($cart));
    }
}
