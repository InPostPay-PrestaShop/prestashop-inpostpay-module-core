<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @extends ObjectRepository<\Product>
 */
class ProductRepository extends ObjectRepository
{
    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\Product::class, $manager);
    }

    public function productExists(int $idProduct): bool
    {
        return null !== $this->find($idProduct);
    }
}
