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
    public function find(int $id, ?int $languageId = null): ?\ObjectModel
    {
        return $this->manager->find($this->class, $id, $languageId);
    }

    /**
     * @return T[]
     */
    public function findAll(?int $languageId = null): array
    {
        $criteria = null === $languageId
            ? []
            : ['id_lang' => $languageId];

        return $this->findBy($criteria, [$this->metadata['primary'] => 'ASC']);
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
        $languageId = isset($criteria['id_lang']) ? (int) $criteria['id_lang'] : null;

        return $this
            ->createFindByQueryBuilder($criteria, $orderBy, $limit, $offset)
            ->build()
            ->getResult($languageId);
    }

    public function createQueryBuilder(string $alias): QueryBuilder
    {
        $qb = $this->manager
            ->createQueryBuilder($this->class)
            ->from($this->metadata['table'], $alias);

        if (empty($this->metadata['multilang'])) {
            return $qb->select($alias . '.*');
        }

        $langAlias = $alias . 'l';
        $langTable = $this->metadata['table'] . '_lang';
        $identifier = $this->metadata['primary'];

        return $qb
            ->select($langAlias . '.*')
            ->select($alias . '.*')
            ->innerJoin($langTable, $langAlias, sprintf('%s.%s = %s.%s', $langAlias, $identifier, $alias, $identifier));
    }

    protected function createFindByQueryBuilder(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');

        $this->applySearchCriteria($qb, $criteria);
        if (null !== $orderBy) {
            $this->applyOrderBy($qb, $orderBy);
        }

        if (null !== $limit || 0 < (int) $offset) {
            $qb->limit($limit, $offset ?? 0);
        }

        return $qb;
    }

    protected function generateAlias(string $field, string $alias): string
    {
        return 'id_lang' !== $field && empty($this->metadata['fields'][$field]['lang'])
            ? $alias
            : $alias . 'l';
    }

    /**
     * @param int|string $field
     */
    protected function getFieldType($field): int
    {
        if ($field === $this->metadata['primary'] || 'id_lang' === $field) {
            return \ObjectModel::TYPE_INT;
        }

        if (!isset($this->metadata['fields'][$field])) {
            throw new \InvalidArgumentException(sprintf('Field "%s" does not exist in %s.', $field, $this->class));
        }

        return $this->metadata['fields'][$field]['type'];
    }

    protected static function escapeQueryParam($value, int $type)
    {
        return \ObjectModel::formatValue($value, $type, true);
    }

    private function applySearchCriteria(QueryBuilder $qb, array $criteria): void
    {
        foreach ($criteria as $field => $value) {
            $type = $this->getFieldType($field);
            $alias = $this->generateAlias($field, 'a');

            if (null === $value) {
                $qb->where(sprintf('%s.%s IS NULL', $alias, $field));
            } elseif (is_array($value)) {
                $value = implode(',', array_map(static function ($value) use ($type) {
                    return self::escapeQueryParam($value, $type);
                }, $value));
                $qb->where(sprintf('%s.%s IN (%s)', $alias, $field, $value));
            } else {
                $value = self::escapeQueryParam($value, $type);
                $qb->where(sprintf('%s.%s = %s', $alias, $field, $value));
            }
        }
    }

    private function applyOrderBy(QueryBuilder $qb, array $orderBy): void
    {
        foreach ($orderBy as $field => $order) {
            $order = \Tools::strtoupper($order);
            if ('ASC' !== $order && 'DESC' !== $order) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid order.', $order));
            }

            // check that field exists in model
            $this->getFieldType($field);

            $alias = $this->generateAlias($field, 'a');
            $qb->orderBy(sprintf('%s.%s %s', $alias, $field, $order));
        }
    }
}
