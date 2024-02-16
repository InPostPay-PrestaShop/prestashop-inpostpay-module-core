<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\Carrier>
 */
class CarrierRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\Carrier::class, $manager);
    }

    public function findOneByReferenceId(int $referenceId, int $languageId = null): ?\Carrier
    {
        if (0 >= $referenceId) {
            return null;
        }

        if (false === $carrier = \Carrier::getCarrierByReference($referenceId, $languageId)) {
            return null;
        }

        return $carrier;
    }
}
