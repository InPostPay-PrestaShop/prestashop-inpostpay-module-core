<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\Analytics\BasketAnalyticsRepository;
use izi\prestashop\ObjectModel\Entity\InPostIziBasketSession;

/**
 * @final
 */
class Version_2_2_0 extends AbstractMigration
{
    private const BS_SHOP_ID_FK = InPostIziBasketSession::TABLE_NAME . '-shop_id';
    private const BASKET_ANALYTICS_CART_ID_FK = BasketAnalyticsRepository::TABLE_NAME . '-cart_id';

    public function getVersion(): string
    {
        return '2.2.0';
    }

    public function up(): void
    {
        $this->updateBasketSessionTable();
        $this->createBasketAnalyticsTable();
    }

    private function updateBasketSessionTable(): void
    {
        $this->addColumn(InPostIziBasketSession::TABLE_NAME, 'id_shop', 'INT(10) DEFAULT NULL');
        $this->addForeignKey(InPostIziBasketSession::TABLE_NAME, 'shop', ['id_shop'], ['id_shop'], self::BS_SHOP_ID_FK, [
            'onDelete' => 'SET NULL',
        ]);
    }

    private function createBasketAnalyticsTable(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . BasketAnalyticsRepository::TABLE_NAME . '` (
                `cart_id` INT(10) UNSIGNED NOT NULL,
                `gclid` VARCHAR(512) DEFAULT NULL,
                `fbclid` VARCHAR(512) DEFAULT NULL,
                `client_id` VARCHAR(512) DEFAULT NULL,
                PRIMARY KEY (`cart_id`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            CHARSET = utf8
            COLLATE = utf8_general_ci
        ');

        $this->addForeignKey(BasketAnalyticsRepository::TABLE_NAME, 'cart', ['cart_id'], ['id_cart'], self::BASKET_ANALYTICS_CART_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
    }
}
