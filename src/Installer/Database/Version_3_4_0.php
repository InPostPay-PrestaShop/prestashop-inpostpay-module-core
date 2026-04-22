<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\InPostDiscount\CartRuleDiscountRepository;

final class Version_3_4_0 extends AbstractMigration
{
    private const DISCOUNT_CART_ID_FK = CartRuleDiscountRepository::TABLE_NAME . '-cart_id';
    private const DISCOUNT_CART_CR_UQ = 'uq_cart_cr_id';
    private const DISCOUNT_CR_IDX = 'idx_cart_rule_id';

    public function getVersion(): string
    {
        return '3.4.0';
    }

    public function up(): void
    {
        $this->createInPostDiscountsTable();
    }

    private function createInPostDiscountsTable(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE `' . _DB_PREFIX_ . CartRuleDiscountRepository::TABLE_NAME . '` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `cart_id` int(10) UNSIGNED NOT NULL,
                `type` varchar(64) NOT NULL,
                `net_amount` decimal(20,6) NOT NULL,
                `gross_amount` decimal(20,6) NOT NULL,
                `cart_rule_id` int(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `' . self::DISCOUNT_CART_CR_UQ . '` UNIQUE (`cart_id`, `cart_rule_id`),
                INDEX `' . self::DISCOUNT_CR_IDX . '` (`cart_rule_id`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            CHARSET = utf8
            COLLATE = utf8_general_ci
        ');

        $this->addForeignKey(CartRuleDiscountRepository::TABLE_NAME, 'cart', ['cart_id'], ['id_cart'], self::DISCOUNT_CART_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
    }
}
