<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\Repository\CartRuleRepository;

class Version_1_11_0 extends AbstractMigration
{
    private const CART_RULE_ID_FK = CartRuleRepository::TABLE_NAME . '-cart_rule_id';

    public function getVersion(): string
    {
        return '1.11.0';
    }

    public function up(): void
    {
        $this->addColumn(Version_1_4_0::SESSIONS_TABLE, 'binding_api_key', 'VARCHAR(255)');
        $this->createCartRuleOptionsTable();
    }

    private function createCartRuleOptionsTable(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . CartRuleRepository::TABLE_NAME . '` (
                `id_cart_rule` INT(10) UNSIGNED NOT NULL,
                `is_omnibus` TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_cart_rule`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            DEFAULT CHARSET = utf8
            COLLATE = utf8_general_ci
        ');

        $this->addForeignKey(CartRuleRepository::TABLE_NAME, 'cart_rule', ['id_cart_rule'], ['id_cart_rule'], self::CART_RULE_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
    }
}
