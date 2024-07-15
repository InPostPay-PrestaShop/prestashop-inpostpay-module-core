<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\ImageType>
 */
class ImageTypeRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\ImageType::class, $manager);
    }

    /**
     * @return \ImageType[]
     */
    public function getProductImageTypes(): array
    {
        return $this->findBy(
            [
                'products' => 1,
            ]
        );
    }
}
