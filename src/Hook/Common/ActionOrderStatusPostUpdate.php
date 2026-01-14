<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Event\OrderStatusUpdatedEvent;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;

final class ActionOrderStatusPostUpdate implements HookInterface
{
    public const HOOK_NAME = 'actionOrderStatusPostUpdate';

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
     * @param array{id_order?: int, newOrderStatus?: \OrderState, oldOrderStatus?: \OrderState} $parameters
     */
    public function execute(array $parameters): void
    {
        $orderId = $parameters['id_order'] ?? null;

        if (!\is_int($orderId)) {
            throw InvalidHookParamException::unexpectedType('id_order', $orderId, 'int');
        }

        $newOrderStatus = $parameters['newOrderStatus'] ?? null;

        if (!$newOrderStatus instanceof \OrderState) {
            throw InvalidHookParamException::unexpectedType('newOrderStatus', $newOrderStatus, \OrderState::class);
        }

        if ($this->context->controller instanceof \ModuleFrontControllerCore && $this->module === $this->context->controller->module) {
            return;
        }

        $this->dispatcher->dispatch(new OrderStatusUpdatedEvent($orderId, $newOrderStatus));
    }
}
