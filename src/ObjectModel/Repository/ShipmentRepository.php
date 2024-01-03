<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\InPostShipmentModel>
 */
class ShipmentRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\InPostShipmentModel::class, $manager);
    }

    /**
     * @return \InPostShipmentModel[]
     */
    public function findWithTrackingNumbersByOrderId(int $orderId): array
    {
        return $this
            ->createQueryBuilder('s')
            ->where('id_order = ' . $orderId)
            ->where('s.tracking_number IS NOT NULL')
            ->build()
            ->getResult();
    }
}
