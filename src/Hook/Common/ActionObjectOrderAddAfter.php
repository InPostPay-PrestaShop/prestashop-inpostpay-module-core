<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Event\OrderEvent;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;

final class ActionObjectOrderAddAfter implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'actionObjectOrderAddAfter';

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

    public static function getVersionRange(): VersionRange
    {
        return new VersionRange(null, '9.0.1');
    }

    /**
     * @param array{order: \Order} $parameters
     */
    public function execute(array $parameters): void
    {
        $order = $parameters['object'] ?? null;

        if (!$order instanceof \Order) {
            throw InvalidHookParamException::unexpectedType('object', $order, \Order::class);
        }

        if ('inpostizi' !== $order->module) {
            return;
        }

        $this->dispatcher->dispatch(new OrderEvent($order), OrderEvent::PERSISTED);
    }
}
