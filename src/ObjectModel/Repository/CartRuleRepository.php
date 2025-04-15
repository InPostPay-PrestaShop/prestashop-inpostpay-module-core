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

    /**
     * @param int $cartRuleId identifier of country restricted cart rule
     *
     * @return \Country[]
     */
    public function getCompatibleCountries(int $cartRuleId): array
    {
        if (0 >= $cartRuleId) {
            return [];
        }

        return $this->manager
            ->createQueryBuilder(\Country::class)
            ->select('cl.*, c.*')
            ->from('cart_rule_country', 'crc')
            ->innerJoin('country', 'c', 'c.id_country = crc.id_country AND c.active = 1')
            ->leftJoin('country_lang', 'cl', 'cl.id_country = c.id_country')
            ->where('crc.id_cart_rule = ' . $cartRuleId)
            ->build()
            ->getResult();
    }
}
