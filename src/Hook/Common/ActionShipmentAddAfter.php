<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Event\ShipmentEvent;
use izi\prestashop\Hook\HookInterface;

final class ActionShipmentAddAfter implements HookInterface
{
    public const HOOK_NAME = 'actionObjectInPostShipmentModelAddAfter';

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
     * @param array{object: \InPostShipmentModel} $parameters
     */
    public function execute(array $parameters): void
    {
        $shipment = $parameters['object'] ?? null;

        if (!$shipment instanceof \InPostShipmentModel) {
            throw new \InvalidArgumentException(sprintf('Parameter "object" expected to be an instance of "%s", "%s" given.', \InPostShipmentModel::class, is_object($shipment) ? get_class($shipment) : gettype($shipment)));
        }

        $this->dispatcher->dispatch(new ShipmentEvent($shipment), ShipmentEvent::CREATED);
    }
}
