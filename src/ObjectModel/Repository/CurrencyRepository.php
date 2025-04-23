<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\Currency>
 */
class CurrencyRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\Currency::class, $manager);
    }

    public function findOneByIsoCode(string $isoCode, ?int $shopId = null): ?\Currency
    {
        if (0 === $currencyId = (int) \Currency::getIdByIsoCode($isoCode, $shopId)) {
            return null;
        }

        return \Currency::getCurrencyInstance($currencyId);
    }
}
