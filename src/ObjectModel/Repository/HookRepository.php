<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\Hook>
 */
class HookRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\Hook::class, $manager);
    }

    /**
     * @return \Hook[]
     */
    public function findByModuleId(int $moduleId): array
    {
        if (0 >= $moduleId) {
            return [];
        }

        return $this->manager
            ->createQueryBuilder($this->class)
            ->select('h.*')
            ->from('hook', 'h')
            ->innerJoin('hook_module', 'hm', 'hm.id_hook = h.id_hook')
            ->where('hm.id_module = ' . $moduleId)
            ->orderBy('h.name')
            ->build()
            ->getResult();
    }
}
