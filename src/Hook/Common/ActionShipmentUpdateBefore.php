<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Event\ShipmentEvent;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;

final class ActionShipmentUpdateBefore implements HookInterface
{
    public const HOOK_NAME = 'actionObjectInPostShipmentModelUpdateBefore';

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

    /**
     * @param array{object?: \InPostShipmentModel} $parameters
     */
    public function execute(array $parameters): void
    {
        $shipment = $parameters['object'] ?? null;

        if (!$shipment instanceof \InPostShipmentModel) {
            throw InvalidHookParamException::unexpectedType('object', $shipment, \InPostShipmentModel::class);
        }

        $this->dispatcher->dispatch(new ShipmentEvent($shipment), ShipmentEvent::BEFORE_UPDATE);
    }
}
