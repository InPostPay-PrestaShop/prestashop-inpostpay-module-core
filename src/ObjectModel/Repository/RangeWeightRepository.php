<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\RangeWeight>
 */
class RangeWeightRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\RangeWeight::class, $manager);
    }

    public function getMaxWeightRangeByCarrier(\Carrier $carrier): ?float
    {
        if (null === $carrier->id || 0 >= $carrier->id) {
            return null;
        }

        $result = $this->manager
            ->createQueryBuilder($this->class)
            ->select('rw.*')
            ->from('range_weight', 'rw')
            ->where('rw.id_carrier = ' . $carrier->id)
            ->orderBy('rw.delimiter2 DESC')
            ->limit(1)
            ->build()
            ->getOneOrNullResult();

        return null !== $result ? (float) $result->delimiter2 : null;
    }
}
