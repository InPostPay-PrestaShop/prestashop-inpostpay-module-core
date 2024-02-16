<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\CMS>
 */
class CmsPageRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\CMS::class, $manager);
    }

    /**
     * @return \CMS[]
     */
    public function findActiveByLanguageAndShopId(int $languageId, int $shopId): array
    {
        $data = \CMS::getCMSPages($languageId, null, true, $shopId);

        return $this->manager->getHydrator()->hydrateCollection($data, $this->getClassName(), $languageId);
    }
}
