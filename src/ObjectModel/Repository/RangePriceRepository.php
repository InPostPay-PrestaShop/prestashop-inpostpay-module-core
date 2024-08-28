<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\RangePrice>
 */
class RangePriceRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\RangePrice::class, $manager);
    }

    public function getMaxPriceRangeByCarrier(\Carrier $carrier): ?float
    {
        if (null === $carrier->id || 0 >= $carrier->id) {
            return null;
        }

        $result = $this->manager
            ->createQueryBuilder($this->class)
            ->select('pr.*')
            ->from('range_price', 'pr')
            ->where('pr.id_carrier = ' . $carrier->id)
            ->orderBy('pr.delimiter2 DESC')
            ->limit(1)
            ->build()
            ->getOneOrNullResult();

        return null !== $result ? (float) $result->delimiter2 : null;
    }
}
