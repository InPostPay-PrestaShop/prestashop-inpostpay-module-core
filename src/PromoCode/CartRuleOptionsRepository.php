<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\ShopAwareConfigurationInterface;
use izi\prestashop\Database\Connection;

final class CartRuleOptionsRepository implements CartRuleOptionsRepositoryInterface
{
    public const TABLE_NAME = 'inpostizi_cart_rule';

    private const OMNIBUS_CACHE_CONFIG_KEY = 'INPOST_PAY_HAS_OMNIBUS_CART_RULES';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    /**
     * @var array<int, CartRuleOptions> options by cart rule ID
     */
    private $options = [];

    public function __construct(Connection $connection, ShopAwareConfigurationInterface $configuration)
    {
        $this->connection = $connection;
        $this->configuration = $configuration;
    }

    /**
     * @internal
     */
    public static function create(): self
    {
        $db = \Db::getInstance();

        return new self(new Connection($db), new Configuration($db));
    }

    public function add(CartRuleOptions $options): void
    {
        $cartRuleId = $options->getCartRuleId();

        try {
            $this->connection->insert(self::TABLE_NAME, [
                'id_cart_rule' => $cartRuleId,
                'is_omnibus' => $options->isOmnibus(),
                'details_cms_id' => $options->getPromoDetailsPageId(),
            ]);
        } catch (\PrestaShopDatabaseException $e) {
            if (1062 !== $e->getCode()) {
                throw $e;
            }

            throw new \DomainException('Cart rule options already exist.');
        }

        $this->options[$cartRuleId] = $options;
        $this->updateOmnibusCache($options);
    }

    public function find(int $cartRuleId): ?CartRuleOptions
    {
        if (0 >= $cartRuleId) {
            return null;
        }

        if (\array_key_exists($cartRuleId, $this->options)) {
            return $this->options[$cartRuleId];
        }

        $qb = $this->createQueryBuilder()->where('id_cart_rule = ' . $cartRuleId);

        return $this->options[$cartRuleId] = $this->getOneOrNullResult($qb);
    }

    public function update(CartRuleOptions $options): void
    {
        $cartRuleId = $options->getCartRuleId();

        $affectedRowCount = $this->connection->update(self::TABLE_NAME, [
            'is_omnibus' => $options->isOmnibus(),
            'details_cms_id' => $options->getPromoDetailsPageId(),
        ], ['id_cart_rule' => $cartRuleId]);

        if (0 === $affectedRowCount) {
            return;
        }

        $this->options[$cartRuleId] = $options;
        $this->updateOmnibusCache($options);
    }

    public function isOmnibus(int $cartRuleId): bool
    {
        if (!$this->configuration->getGlobal(self::OMNIBUS_CACHE_CONFIG_KEY)) {
            return false;
        }

        if (null === $options = $this->find($cartRuleId)) {
            return false;
        }

        return $options->isOmnibus();
    }

    protected function createQueryBuilder(): \DbQuery
    {
        return (new \DbQuery())->from(self::TABLE_NAME);
    }

    protected function getOneOrNullResult(\DbQuery $qb): ?CartRuleOptions
    {
        if (false === $row = $this->connection->fetchAssociative((string) $qb)) {
            return null;
        }

        return $this->hydrate($row);
    }

    protected function hydrate(array $row): CartRuleOptions
    {
        return (new CartRuleOptions((int) $row['id_cart_rule']))
            ->setIsOmnibus((bool) $row['is_omnibus'])
            ->setPromoDetailsPageId($row['details_cms_id'] ? (int) $row['details_cms_id'] : null);
    }

    private function updateOmnibusCache(CartRuleOptions $options): void
    {
        $hasOmnibusRules = $options->isOmnibus() || $this->hasOmnibusRules();
        $this->configuration->setGlobal(self::OMNIBUS_CACHE_CONFIG_KEY, $hasOmnibusRules);
    }

    private function hasOmnibusRules(): bool
    {
        $qb = $this
            ->createQueryBuilder()
            ->select('1')
            ->where('is_omnibus = 1');

        return (bool) $this->connection->fetchOne('SELECT EXISTS(' . $qb . ')');
    }
}
