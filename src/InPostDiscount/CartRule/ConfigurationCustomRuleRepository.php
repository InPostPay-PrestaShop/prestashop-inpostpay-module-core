<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule;

use Doctrine\DBAL\Connection;
use izi\prestashop\Configuration\ShopAwareConfigurationInterface;

final class ConfigurationCustomRuleRepository implements CustomRuleRepositoryInterface
{
    private const CART_RULE_ID_PATTERN = 'INPOST_PAY_{discount_type}_CART_RULE_ID';
    private const CART_RULE_IDS_KEY = 'INPOST_PAY_INPOST_DISCOUNT_CART_RULE_IDS';

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    public function __construct(ShopAwareConfigurationInterface $configuration, Connection $connection, string $dbPrefix = _DB_PREFIX_)
    {
        $this->configuration = $configuration;
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
    }

    public function registerCartRule(string $discountType, int $cartRuleId): void
    {
        $key = $this->getCartRuleIdConfigKey($discountType);
        $this->configuration->setGlobal($key, $cartRuleId);
        $this->appendCustomRuleId($cartRuleId);
    }

    public function getCartRuleId(string $discountType): ?int
    {
        $key = $this->getCartRuleIdConfigKey($discountType);
        $value = $this->configuration->getGlobal($key);

        return null === $value ? null : (int) $value;
    }

    public function getAllCustomRuleIds(): array
    {
        if (null === $value = $this->configuration->getGlobal(self::CART_RULE_IDS_KEY)) {
            return [];
        }

        return array_map('intval', explode(',', $value));
    }

    public function isCustomCartRule(int $cartRuleId): bool
    {
        $ids = $this->getAllCustomRuleIds();

        return \in_array($cartRuleId, $ids, true);
    }

    private function getCartRuleIdConfigKey(string $discountType): string
    {
        return strtr(self::CART_RULE_ID_PATTERN, ['{discount_type}' => $discountType]);
    }

    private function appendCustomRuleId(int $cartRuleId): void
    {
        $this->connection->beginTransaction();

        try {
            $sql = $this->connection->createQueryBuilder()
                ->select('id_configuration, value')
                ->from($table = $this->dbPrefix . 'configuration')
                ->where('name = :key')
                ->getSQL() . ' FOR UPDATE';

            $result = $this->connection->executeQuery($sql, [
                'key' => self::CART_RULE_IDS_KEY,
            ]);

            $now = date('Y-m-d H:i:s');
            $row = method_exists($result, 'fetchAssociative') ? $result->fetchAssociative() : $result->fetch(\PDO::FETCH_ASSOC);

            if (false === $row) {
                $value = (string) $cartRuleId;
                $this->connection->insert($table, [
                    'name' => self::CART_RULE_IDS_KEY,
                    'id_shop_group' => 0,
                    'id_shop' => 0,
                    'value' => $value,
                    'date_add' => $now,
                    'date_upd' => $now,
                ]);
            } else {
                $value = $row['value'] . ',' . $cartRuleId;
                $this->connection->update($table, [
                    'value' => $value,
                    'date_upd' => $now,
                ], [
                    'id_configuration' => (int) $row['id_configuration'],
                ]);
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }

        \Configuration::set(self::CART_RULE_IDS_KEY, $value, 0, 0);
    }
}
