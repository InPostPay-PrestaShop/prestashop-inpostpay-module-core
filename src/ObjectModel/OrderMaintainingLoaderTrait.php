<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel;

trait OrderMaintainingLoaderTrait
{
    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    /**
     * @template TKey of mixed
     * @template TVal of \ObjectModel
     *
     * @param class-string<TVal> $class
     * @param array<TKey, int> $ids list of object identifiers
     *
     * @return array<TKey, TVal> objects indexed by corresponding keys from the $ids array and in the same relative order
     */
    private function findByIdsMaintainingOrder(string $class, array $ids, ?int $languageId = null, ?int $shopId = null): array
    {
        if ([] === $ids) {
            return [];
        }

        $criteria = ['id' => $ids];

        if (null !== $languageId) {
            $criteria['id_lang'] = $languageId;
        }

        if (null !== $shopId) {
            $criteria['id_shop'] = $shopId;
        }

        if ([] === $objects = $this->manager->getRepository($class)->findBy($criteria)) {
            return [];
        }

        $objectsById = [];
        foreach ($objects as $object) {
            $objectsById[$object->id] = $object;
        }

        $result = [];
        foreach ($ids as $i => $id) {
            if (isset($objectsById[$id])) {
                $result[$i] = $objectsById[$id];
            }
        }

        return $result;
    }
}
