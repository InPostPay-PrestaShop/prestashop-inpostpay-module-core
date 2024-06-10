<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel;

use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

/**
 * @method remove(\ObjectModel $model)
 */
interface ObjectManagerInterface
{
    public function getConnection(): Connection;

    public function getHydrator(): HydratorInterface;

    public function save(\ObjectModel $model);

    /**
     * @template T of \ObjectModel
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function find(string $class, int $id, ?int $languageId = null): ?\ObjectModel;

    public function refresh(\ObjectModel $model);

    /**
     * @template T of \ObjectModel
     *
     * @param class-string<T> $class
     */
    public function getMetadata(string $class): array;

    /**
     * @template T of \ObjectModel
     *
     * @param class-string<T> $class
     *
     * @return ObjectRepositoryInterface<T>
     */
    public function getRepository(string $class): ObjectRepositoryInterface;

    /**
     * @template T of \ObjectModel
     *
     * @param class-string<T> $class
     *
     * @return QueryBuilder<T>
     */
    public function createQueryBuilder(string $class): QueryBuilder;
}
