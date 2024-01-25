<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel;

interface HydratorInterface
{
    /**
     * @template T of \ObjectModel
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function hydrate(array $data, string $class, \ObjectModel $model = null, int $languageId = null): \ObjectModel;

    /**
     * @template T of \ObjectModel
     *
     * @param class-string<T> $class
     *
     * @return T[]
     */
    public function hydrateCollection(array $data, string $class, int $languageId = null): array;
}
