<?php

declare(strict_types=1);

namespace izi\prestashop\Event;

final class ShipmentEvent extends Event
{
    public const CREATED = 'inpostizi.shipment.created';
    public const BEFORE_UPDATE = 'inpostizi.shipment.before_update';
    public const UPDATED = 'inpostizi.shipment.updated';

    /**
     * @var \InPostShipmentModel
     */
    private $shipment;

    public function __construct(\InPostShipmentModel $shipment)
    {
        $this->shipment = $shipment;
    }

    public function getShipment(): \InPostShipmentModel
    {
        return $this->shipment;
    }
}
