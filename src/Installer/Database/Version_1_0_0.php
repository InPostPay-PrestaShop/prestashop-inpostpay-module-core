<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\ObjectModel\Entity\InPostIziBasketSession;

/**
 * initial DB schema
 */
final class Version_1_0_0 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function up(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . InPostIziBasketSession::TABLE_NAME . '` (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                session_id TEXT,
                confirmation_response TEXT,
                cart_id VARCHAR(255),
                order_id INTEGER,
                order_details TEXT,
                redirect_url VARCHAR(255),
                basket_cache TEXT,
                basket_cached TEXT,
                coupons TEXT,
                event BIT(1),
                redirected SMALLINT(1) DEFAULT 0,
                PRIMARY KEY (id)
            ) DEFAULT CHARSET=utf8;
        ');
    }
}
