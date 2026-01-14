<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\Configuration>
 */
class ConfigurationRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\Configuration::class, $manager);
    }

    /**
     * @return \Configuration[]
     */
    public function findByNamePrefix(string $prefix): array
    {
        return $this->manager
            ->createQueryBuilder($this->class)
            ->select('cl.*, c.*, COALESCE(cl.`value`, c.`value`) AS `value`')
            ->from('configuration', 'c')
            ->leftJoin('configuration_lang', 'cl', 'cl.id_configuration = c.id_configuration')
            ->where(\sprintf('c.name LIKE "%s%%"', pSQL($prefix)))
            ->orderBy('c.id_configuration')
            ->build()
            ->getResult();
    }
}
