<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct;

use izi\prestashop\Database\Connection;
use izi\prestashop\Product\ReferenceId;
use Psr\Clock\ClockInterface;

class HotProductRepository implements HotProductRepositoryInterface
{
    public const TABLE_NAME = 'inpostizi_hot_product';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ClockInterface
     */
    private $clock;

    /**
     * @var \ReflectionClass
     */
    private $reflection;

    public function __construct(Connection $connection, ClockInterface $clock)
    {
        $this->connection = $connection;
        $this->clock = $clock;
        $this->reflection = new \ReflectionClass(HotProduct::class);
    }

    public function add(HotProduct $product): void
    {
        if (null !== $product->getId()) {
            throw new \DomainException('Hot product already exists.');
        }

        $this->updateTimestamps($product, $now = $this->clock->now());

        $this->connection->insert(self::TABLE_NAME, [
            'product_id' => $product->getProductId(),
            'combination_id' => $product->getCombinationId(),
            'shop_id' => $product->getShopId(),
            'reference_id' => (string) $product->getReferenceId(),
            'available_from' => ($from = $product->getAvailableFrom()) ? $from->format('Y-m-d H:i:s') : null,
            'available_to' => ($to = $product->getAvailableTo()) ? $to->format('Y-m-d H:i:s') : null,
            'updated_at' => $now->format('Y-m-d H:i:s'),
            'created_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $this->setPropertyValue($product, 'id', (int) $this->connection->getLastInsertId());
    }

    public function find(int $id): ?HotProduct
    {
        $qb = $this->createQueryBuilder()->where('id = ' . $id);

        return $this->getOneOrNullResult($qb);
    }

    /**
     * @return HotProduct[]
     */
    public function findAll(?int $shopId = null): array
    {
        $qb = $this
            ->createQueryBuilder($shopId)
            ->orderBy('product_id, combination_id');

        $rows = $this->connection->fetchAllAssociative((string) $qb);

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @param string[]|ReferenceId[] $referenceIds
     */
    public function findBy(?int $shopId = null, ?int $limit = null, ?int $offset = null, array $referenceIds = []): array
    {
        $qb = $this
            ->createByReferenceIdsQueryBuilder($shopId, $referenceIds)
            ->orderBy('product_id, combination_id');

        $rows = $this->connection->fetchAllAssociative((string) $qb);

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @param string[]|ReferenceId[] $referenceIds
     */
    public function countBy(?int $shopId = null, array $referenceIds = []): int
    {
        $qb = $this
            ->createByReferenceIdsQueryBuilder($shopId, $referenceIds)
            ->select('COUNT(*)');

        return (int) $this->connection->fetchOne((string) $qb);
    }

    public function findOneByReferenceId(string $referenceId, ?int $shopId = null): ?HotProduct
    {
        $qb = $this
            ->createQueryBuilder($shopId)
            ->where('reference_id = "' . pSQL($referenceId) . '"');

        return $this->getOneOrNullResult($qb);
    }

    public function findOneByProductId(int $productId, ?int $combinationId = null, ?int $shopId = null): ?HotProduct
    {
        $qb = $this
            ->createQueryBuilder($shopId)
            ->where('product_id = ' . $productId);

        if (null !== $combinationId) {
            $qb->where('combination_id = ' . $combinationId);
        } else {
            $qb->where('combination_id IS NULL');
        }

        return $this->getOneOrNullResult($qb);
    }

    public function update(HotProduct $product): void
    {
        if (null === $id = $product->getId()) {
            throw new \DomainException('Cannot update a product that has not been persisted.');
        }

        $this->updateTimestamps($product, $now = $this->clock->now());

        $this->connection->update(self::TABLE_NAME, [
            'available_from' => ($from = $product->getAvailableFrom()) ? $from->format('Y-m-d H:i:s') : null,
            'available_to' => ($to = $product->getAvailableTo()) ? $to->format('Y-m-d H:i:s') : null,
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ], [
            'id' => $id,
        ]);
    }

    public function remove(HotProduct $product): void
    {
        if (null === $id = $product->getId()) {
            throw new \DomainException('Cannot remove a product that has not been persisted.');
        }

        $this->connection->delete(self::TABLE_NAME, [
            'id' => $id,
        ]);
    }

    protected function createQueryBuilder(?int $shopId = null): \DbQuery
    {
        $qb = (new \DbQuery())->from(self::TABLE_NAME);

        return null === $shopId ? $qb : $qb->where('shop_id IS NULL OR shop_id = ' . $shopId);
    }

    /**
     * @param string[]|ReferenceId[] $referenceIds
     */
    protected function createByReferenceIdsQueryBuilder(?int $shopId, array $referenceIds): \DbQuery
    {
        $qb = $this->createQueryBuilder($shopId);

        if ([] === $referenceIds) {
            return $qb;
        }

        $referenceIds = array_map(static function ($referenceId): string {
            return pSQL((string) $referenceId);
        }, $referenceIds);

        return $qb->where('reference_id IN ("' . implode('","', $referenceIds) . '")');
    }

    protected function getOneOrNullResult(\DbQuery $qb): ?HotProduct
    {
        if (false === $row = $this->connection->fetchAssociative((string) $qb)) {
            return null;
        }

        return $this->hydrate($row);
    }

    protected function hydrate(array $row): HotProduct
    {
        $product = new HotProduct(
            (int) $row['product_id'],
            $row['combination_id'] ? (int) $row['combination_id'] : null,
            $row['shop_id'] ? (int) $row['shop_id'] : null,
            $row['available_from'] ? new \DateTimeImmutable($row['available_from']) : null,
            $row['available_to'] ? new \DateTimeImmutable($row['available_to']) : null,
            ReferenceId::fromString($row['reference_id'])
        );

        $this->setPropertyValue($product, 'id', (int) $row['id']);
        $this->setPropertyValue($product, 'createdAt', new \DateTimeImmutable($row['created_at']));
        $this->setPropertyValue($product, 'updatedAt', new \DateTimeImmutable($row['updated_at']));

        return $product;
    }

    private function updateTimestamps(HotProduct $product, \DateTimeImmutable $now): void
    {
        $this->setPropertyValue($product, 'updatedAt', $now);
        if (null === $product->getCreatedAt()) {
            $this->setPropertyValue($product, 'createdAt', $now);
        }
    }

    /**
     * @param string $name property name to set
     * @param mixed $value property value
     */
    private function setPropertyValue(HotProduct $product, string $name, $value): void
    {
        $property = $this->reflection->getProperty($name);
        if (80100 > \PHP_VERSION_ID) {
            $property->setAccessible(true);
        }
        $property->setValue($product, $value);
    }
}
