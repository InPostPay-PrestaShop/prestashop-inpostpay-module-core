<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\CartRule>
 */
class CartRuleRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\CartRule::class, $manager);
    }

    public function isCompatibleWithCountry(int $cartRuleId, string $isoCode): bool
    {
        if (0 >= $cartRuleId) {
            return false;
        }

        $qb = (new \DbQuery())
            ->select('1')
            ->from('cart_rule_country', 'crc')
            ->innerJoin('country', 'c', 'c.id_country = crc.id_country AND c.active = 1')
            ->where('crc.id_cart_rule = ' . $cartRuleId)
            ->where('c.iso_code = "' . pSQL($isoCode) . '"');

        return (bool) $this->manager->getConnection()->fetchOne('SELECT EXISTS(' . $qb . ')');
    }
}
