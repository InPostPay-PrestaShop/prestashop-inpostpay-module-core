<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel;

use izi\prestashop\Database\Connection;
use izi\prestashop\ObjectModel\Exception\InvalidDataException;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryFactoryInterface;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

final class ObjectManager implements ObjectManagerInterface
{
    /**
     * @var ObjectRepositoryFactoryInterface
     */
    private $repositoryFactory;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var HydratorInterface
     */
    private $hydrator;

    /**
     * @var array<string, array> metadata by class
     */
    private $metadata = [];

    public function __construct(Connection $connection, ObjectRepositoryFactoryInterface $repositoryFactory, HydratorInterface $hydrator)
    {
        $this->repositoryFactory = $repositoryFactory;
        $this->connection = $connection;
        $this->hydrator = $hydrator;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getHydrator(): HydratorInterface
    {
        return $this->hydrator;
    }

    public function save(\ObjectModel $model): void
    {
        $id = (int) $model->id;

        $this->validateModel($model);

        if (false === $this->connection->execute(\Closure::fromCallable([$model, 'save']))) {
            $message = 0 >= $id
                ? \sprintf('Failed to create a new %s.', \get_class($model))
                : \sprintf('Failed to update %s ID %d.', \get_class($model), $id);

            throw new \RuntimeException($message);
        }
    }

    public function remove(\ObjectModel $model): void
    {
        if (0 >= $id = (int) $model->id) {
            return;
        }

        if (false === $this->connection->execute(\Closure::fromCallable([$model, 'delete']))) {
            throw new \RuntimeException(\sprintf('Failed to delete %s ID %d.', \get_class($model), $id));
        }
    }

    /**
     * @template T of \ObjectModel
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function find(string $class, int $id, ?int $languageId = null, ?int $shopId = null): ?\ObjectModel
    {
        if (0 >= $id) {
            return null;
        }

        $args = \Product::class === $class
            ? [$id, false, $languageId, $shopId]
            : [$id, $languageId, $shopId];

        $model = new $class(...$args);

        return $id === (int) $model->id ? $model : null;
    }

    public function refresh(\ObjectModel $model): void
    {
        $class = \get_class($model);
        $metadata = $this->getMetadata($class);

        $languageId = $metadata['multilang']
            ? (\Closure::bind(function (): ?int {
                return isset($this->id_lang) ? (int) $this->id_lang : null;
            }, $model, \ObjectModel::class))()
            : null;

        $shopId = $metadata['multishop'] ? $this->getShopId($model) : null;

        $data = $this
            ->getRepository($class)
            ->createQueryBuilder('a', $languageId, $shopId)
            ->where(\sprintf('a.%s = %d', $metadata['primary'], (int) $model->id))
            ->build()
            ->getArrayResult();

        $this->hydrator->hydrate($data, $class, $model, $languageId);
    }

    /**
     * @template T of \ObjectModel
     *
     * @param class-string<T> $class
     */
    public function getMetadata(string $class): array
    {
        if (isset($this->metadata[$class])) {
            return $this->metadata[$class];
        }

        if (!is_subclass_of($class, \ObjectModel::class)) {
            throw new \DomainException(\sprintf('%s is not a %s.', $class, \ObjectModel::class));
        }

        $metadata = $class::getDefinition($class);
        $metadata['multishop'] = \Shop::isTableAssociated($metadata['table']);

        if (!\array_key_exists('multilang', $metadata)) {
            $metadata['multilang'] = false;
        }

        if (!\array_key_exists('multilang_shop', $metadata)) {
            $metadata['multilang_shop'] = false;
        }

        return $this->metadata[$class] = $metadata;
    }

    public function getRepository(string $class): ObjectRepositoryInterface
    {
        return $this->repositoryFactory->getRepository($this, $class);
    }

    public function createQueryBuilder(string $class, ?int $languageId = null): QueryBuilder
    {
        return new QueryBuilder($this, $class, $languageId);
    }

    private function validateModel(\ObjectModel $model): void
    {
        try {
            $model->validateFields();
            $model->validateFieldsLang();
        } catch (\PrestaShopException $e) {
            throw new InvalidDataException($e->getMessage(), 0, $e);
        }
    }

    private function getShopId(\ObjectModel $model): ?int
    {
        if (\is_callable([$model, 'getShopId'])) {
            return $model->getShopId();
        }

        return (\Closure::bind(function (): ?int {
            return isset($this->id_shop) ? (int) $this->id_shop : null;
        }, $model, \ObjectModel::class))();
    }
}
