<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\Repository\ShipmentRepository;

final class CarrierModuleTrackingNumberProvider implements TrackingNumberProviderInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    public function __construct(ObjectManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getTrackingNumbers(int $orderId): array
    {
        if (!class_exists(\InPostShipmentModel::class)) {
            return [];
        }

        /** @var ShipmentRepository $repository */
        $repository = $this->manager->getRepository(\InPostShipmentModel::class);
        $shipments = $repository->findWithTrackingNumbersByOrderId($orderId);

        return array_map(static function (\InPostShipmentModel $shipment): string {
            return $shipment->tracking_number;
        }, $shipments);
    }
}
