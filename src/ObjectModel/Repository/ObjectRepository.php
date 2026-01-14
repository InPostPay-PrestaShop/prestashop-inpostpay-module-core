<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\QueryBuilder;

/**
 * @template T of \ObjectModel
 */
class ObjectRepository implements ObjectRepositoryInterface
{
    /**
     * @var class-string<T>
     */
    protected $class;

    /**
     * @var array
     */
    protected $metadata;

    /**
     * @var ObjectManagerInterface
     */
    protected $manager;

    /**
     * @param class-string<T> $class
     */
    public function __construct(string $class, ObjectManagerInterface $manager)
    {
        $this->class = $class;
        $this->metadata = $manager->getMetadata($class);
        $this->manager = $manager;
    }

    /**
     * @return class-string<T>
     */
    public function getClassName(): string
    {
        return $this->class;
    }

    /**
     * @return T|null
     */
    public function find(int $id, ?int $languageId = null, ?int $shopId = null): ?\ObjectModel
    {
        return $this->manager->find($this->class, $id, $languageId, $shopId);
    }

    /**
     * @return T[]
     */
    public function findAll(?int $languageId = null, ?int $shopId = null): array
    {
        $criteria = [];

        if (null !== $languageId && $this->metadata['multilang']) {
            $criteria['id_lang'] = $languageId;
        }

        if (null !== $shopId && $this->metadata['multishop']) {
            $criteria['id_shop'] = $shopId;
        }

        return $this->findBy($criteria, ['id' => 'ASC']);
    }

    /**
     * @return T|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?\ObjectModel
    {
        $collection = $this->findBy($criteria, $orderBy, 1);

        return [] === $collection ? null : current($collection);
    }

    /**
     * @return T[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this
            ->createFindByQueryBuilder($criteria, $orderBy, $limit, $offset)
            ->build()
            ->getResult();
    }

    /**
     * @return QueryBuilder<T>
     */
    public function createQueryBuilder(string $alias, ?int $languageId = null, ?int $shopId = null): QueryBuilder
    {
        $identifier = $this->metadata['primary'];

        $qb = $this->manager
            ->createQueryBuilder($this->class, $languageId)
            ->from($this->metadata['table'], $alias);

        if ($this->metadata['multilang']) {
            $langAlias = $alias . 'l';
            $langTable = $this->metadata['table'] . '_lang';

            $joinConditions = [
                \sprintf('%s.%s = %s.%s', $langAlias, $identifier, $alias, $identifier),
            ];

            if (null !== $languageId) {
                $joinConditions[] = \sprintf('%s.id_lang = %d', $langAlias, $languageId);
            }

            if (null !== $shopId && $this->metadata['multilang_shop']) {
                $joinConditions[] = \sprintf('%s.id_shop = %d', $langAlias, $shopId);
            }

            $qb
                ->select($langAlias . '.*')
                ->select($alias . '.*') // assure primary key is not overwritten if the translation in a given language does not exist
                ->leftJoin($langTable, $langAlias, implode(' AND ', $joinConditions));
        } else {
            $qb->select($alias . '.*');
        }

        if (null !== $shopId && $this->metadata['multishop']) {
            $shopAlias = $alias . '_shop';
            $shopTable = $this->metadata['table'] . '_shop';

            $joinConditions = [
                \sprintf('%s.%s = %s.%s', $shopAlias, $identifier, $alias, $identifier),
                \sprintf('%s.id_shop = %d', $shopAlias, $shopId),
            ];

            $qb
                ->select($shopAlias . '.*')
                ->innerJoin($shopTable, $shopAlias, implode(' AND ', $joinConditions));
        }

        return $qb;
    }

    protected function createFindByQueryBuilder(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): QueryBuilder
    {
        if ($this->metadata['multilang'] && isset($criteria['id_lang']) && is_numeric($criteria['id_lang'])) {
            $languageId = (int) $criteria['id_lang'];
            unset($criteria['id_lang']);
        } else {
            $languageId = null;
        }

        if ($this->metadata['multishop'] && isset($criteria['id_shop'])) {
            $shopId = (int) $criteria['id_shop'];
            unset($criteria['id_shop']);
        } else {
            $shopId = null;
        }

        $qb = $this->createQueryBuilder('a', $languageId, $shopId);

        $this->applySearchCriteria($qb, $criteria, $shopId);
        if (null !== $orderBy) {
            $this->applyOrderBy($qb, $orderBy, $shopId);
        }

        if (null !== $limit || 0 < (int) $offset) {
            $qb->limit($limit, $offset ?? 0);
        }

        return $qb;
    }

    protected function generateAlias(string $field, string $alias, ?int $shopId = null): string
    {
        if (
            $this->metadata['multilang']
            && ('id_lang' === $field || !empty($this->metadata['fields'][$field]['lang']))
        ) {
            return $alias . 'l';
        }

        if (null !== $shopId && !empty($this->metadata['fields'][$field]['shop'])) {
            return $alias . '_shop';
        }

        return $alias;
    }

    /**
     * @param int|string $field
     */
    protected function getFieldType($field): int
    {
        if (
            $field === $this->metadata['primary']
            || 'id_lang' === $field && $this->metadata['multilang']
            || 'id_shop' === $field && $this->metadata['multishop']
        ) {
            return \ObjectModel::TYPE_INT;
        }

        if (!isset($this->metadata['fields'][$field])) {
            throw new \InvalidArgumentException(\sprintf('Field "%s" does not exist in %s.', $field, $this->class));
        }

        return $this->metadata['fields'][$field]['type'];
    }

    protected static function escapeQueryParam($value, int $type)
    {
        return \ObjectModel::formatValue($value, $type, true);
    }

    private function applySearchCriteria(QueryBuilder $qb, array $criteria, ?int $shopId = null): void
    {
        foreach ($criteria as $field => $value) {
            if ('id' === $field) {
                $field = $this->metadata['primary'];
            }

            $type = $this->getFieldType($field);
            $alias = $this->generateAlias($field, 'a', $shopId);

            if (null === $value) {
                $qb->where(\sprintf('%s.%s IS NULL', $alias, $field));
            } elseif (\is_array($value)) {
                $value = implode(',', array_map(static function ($value) use ($type) {
                    return self::escapeQueryParam($value, $type);
                }, $value));
                $qb->where(\sprintf('%s.%s IN (%s)', $alias, $field, $value));
            } else {
                $value = self::escapeQueryParam($value, $type);
                $qb->where(\sprintf('%s.%s = %s', $alias, $field, $value));
            }
        }
    }

    private function applyOrderBy(QueryBuilder $qb, array $orderBy, ?int $shopId = null): void
    {
        foreach ($orderBy as $field => $order) {
            $order = \Tools::strtoupper($order);
            if ('ASC' !== $order && 'DESC' !== $order) {
                throw new \InvalidArgumentException(\sprintf('"%s" is not a valid order.', $order));
            }

            if ('id' === $field) {
                $field = $this->metadata['primary'];
            }

            // check that field exists in model
            $this->getFieldType($field);

            $alias = $this->generateAlias($field, 'a', $shopId);
            $qb->orderBy(\sprintf('%s.%s %s', $alias, $field, $order));
        }
    }
}
