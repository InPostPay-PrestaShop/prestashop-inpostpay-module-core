<?php

declare(strict_types=1);

namespace izi\prestashop\Repository;

use izi\prestashop\Configuration\ShopAwareConfigurationInterface;
use izi\prestashop\Database\Connection;

class CartRuleRepository implements CartRuleRepositoryInterface
{
    public const TABLE_NAME = 'inpostizi_cart_rule';

    private const OMNIBUS_CACHE_CONFIG_KEY = 'INPOST_PAY_OMNIBUS_CART_RULE_ID';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    /**
     * @var array<int, array> options by cart rule ID
     */
    private $options = [];

    public function __construct(Connection $connection, ShopAwareConfigurationInterface $configuration)
    {
        $this->connection = $connection;
        $this->configuration = $configuration;
    }

    public function isOmnibus(int $cartRuleId): bool
    {
        if (0 >= $cartRuleId) {
            return false;
        }

        if (!$this->configuration->getGlobal(self::OMNIBUS_CACHE_CONFIG_KEY)) {
            return false;
        }

        $options = $this->getOptions($cartRuleId);

        return $options['is_omnibus'] ?? false;
    }

    public function setOmnibus(int $cartRuleId, bool $isOmnibus): void
    {
        $data = [
            'id_cart_rule' => $cartRuleId,
            'is_omnibus' => $isOmnibus,
        ];

        if (null === $options = $this->getOptions($cartRuleId)) {
            $this->connection->insert(self::TABLE_NAME, $data);
        } elseif ($isOmnibus !== $options['is_omnibus']) {
            $this->connection->update(self::TABLE_NAME, [
                'is_omnibus' => $isOmnibus,
            ], ['id_cart_rule' => $cartRuleId]);
        } else {
            return; // no update needed
        }

        $this->options[$cartRuleId]['is_omnibus'] = $isOmnibus;

        $hasOmnibusRules = $isOmnibus || $this->hasOmnibusRules();
        $this->configuration->setGlobal(self::OMNIBUS_CACHE_CONFIG_KEY, $hasOmnibusRules);
    }

    private function getOptions(int $cartRuleId): ?array
    {
        if (array_key_exists($cartRuleId, $this->options)) {
            return $this->options[$cartRuleId];
        }

        $qb = $this
            ->createQueryBuilder()
            ->select('is_omnibus')
            ->where('id_cart_rule = ' . $cartRuleId);

        if (false === $data = $this->connection->fetchAssociative((string) $qb)) {
            return $this->options[$cartRuleId] = null;
        }

        return $this->options[$cartRuleId] = [
            'is_omnibus' => (bool) $data['is_omnibus'],
        ];
    }

    private function hasOmnibusRules(): bool
    {
        $qb = $this
            ->createQueryBuilder()
            ->select('1')
            ->where('is_omnibus = 1');

        return (bool) $this->connection->fetchOne('SELECT EXISTS(' . $qb . ')');
    }

    private function createQueryBuilder(): \DbQuery
    {
        return (new \DbQuery())->from(self::TABLE_NAME);
    }
}
