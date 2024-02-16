<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\QueryBuilder;

/**
 * @template T of \ObjectModel
 */
interface ObjectRepositoryInterface
{
    /**
     * @return class-string<T>
     */
    public function getClassName(): string;

    /**
     * @return T|null
     */
    public function find(int $id, int $languageId = null): ?\ObjectModel;

    /**
     * @return T[]
     */
    public function findAll(int $languageId = null): array;

    /**
     * @return T|null
     */
    public function findOneBy(array $criteria, array $orderBy = null): ?\ObjectModel;

    /**
     * @return T[]
     */
    public function findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null): array;

    public function createQueryBuilder(string $alias): QueryBuilder;
}
