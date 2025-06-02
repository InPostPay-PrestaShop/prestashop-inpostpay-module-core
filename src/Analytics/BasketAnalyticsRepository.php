<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics;

use izi\prestashop\Database\Connection;

class BasketAnalyticsRepository implements BasketAnalyticsRepositoryInterface
{
    public const TABLE_NAME = 'inpostizi_basket_analytics';

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function add(BasketAnalytics $basketAnalytics): void
    {
        $this->connection->insert(self::TABLE_NAME, [
            'cart_id' => $basketAnalytics->getCartId(),
            'gclid' => $basketAnalytics->getGclid(),
            'fbclid' => $basketAnalytics->getFbclid(),
            'client_id' => $basketAnalytics->getClientId(),
        ]);
    }

    public function find(int $id): ?BasketAnalyticsInterface
    {
        $qb = $this->createQueryBuilder()->where('cart_id = ' . $id);

        return $this->getOneOrNullResult($qb);
    }

    public function remove(int $id): void
    {
        $this->connection->delete(self::TABLE_NAME, [
            'cart_id' => $id,
        ]);
    }

    public function save(BasketAnalytics $basketAnalytics): void
    {
        $exists = $this->find($basketAnalytics->getCartId());

        if (null === $exists) {
            $this->add($basketAnalytics);

            return;
        }

        $this->connection->update(self::TABLE_NAME, [
            'gclid' => $basketAnalytics->getGclid(),
            'fbclid' => $basketAnalytics->getFbclid(),
            'client_id' => $basketAnalytics->getClientId(),
        ], [
            'cart_id' => $basketAnalytics->getCartId(),
        ]);
    }

    protected function createQueryBuilder(): \DbQuery
    {
        return (new \DbQuery())->from(self::TABLE_NAME);
    }

    protected function getOneOrNullResult(\DbQuery $qb): ?BasketAnalytics
    {
        if (false === $row = $this->connection->fetchAssociative((string) $qb)) {
            return null;
        }

        return $this->hydrate($row);
    }

    protected function hydrate(array $row): BasketAnalytics
    {
        return new BasketAnalytics(
            (int)$row['cart_id'],
            $row['gclid'],
            $row['fbclid'],
            $row['client_id']
        );
    }
}
