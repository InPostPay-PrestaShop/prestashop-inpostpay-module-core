<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

interface ObjectRepositoryFactoryInterface
{
    /**
     * @template T of \ObjectModel
     *
     * @param class-string<T> $class
     *
     * @return ObjectRepositoryInterface<T>
     */
    public function getRepository(ObjectManagerInterface $manager, string $class): ObjectRepositoryInterface;
}
