<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\HotProduct\HotProductRepository;
use izi\prestashop\PromoCode\CartRuleOptionsRepository;

class Version_2_1_0 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '2.1.0';
    }

    public function up(): void
    {
        $this->createHotProductsTable();
        $this->updateCartRuleOptionsTable();
    }

    private function createHotProductsTable(): void
    {
        if ($this->tableExists(HotProductRepository::TABLE_NAME)) {
            return;
        }

        $this->connection->executeStatement('
            CREATE TABLE `' . _DB_PREFIX_ . HotProductRepository::TABLE_NAME . '` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `product_id` int(11) unsigned NOT NULL,
                `combination_id` int(11) unsigned,
                `shop_id` int(11),
                `reference_id` varchar(32) NOT NULL,
                `available_from` datetime,
                `available_to` datetime,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `inpostizi_hot_product_reference_id_unique` (`reference_id`, `shop_id`),
                KEY `inpostizi_hot_product_combination_idx` (`product_id`, `combination_id`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            CHARSET = utf8
            COLLATE = utf8_general_ci
        ');

        $this->addForeignKey(HotProductRepository::TABLE_NAME, 'product', ['product_id'], ['id_product'], 'inpostizi_hot_product-product_id', [
            'onDelete' => 'CASCADE',
        ]);

        $this->addForeignKey(HotProductRepository::TABLE_NAME, 'product_attribute', ['combination_id'], ['id_product_attribute'], 'inpostizi_hot_product-combination_id', [
            'onDelete' => 'CASCADE',
        ]);

        $this->addForeignKey(HotProductRepository::TABLE_NAME, 'shop', ['shop_id'], ['id_shop'], 'inpostizi_hot_product-shop_id', [
            'onDelete' => 'CASCADE',
        ]);
    }

    private function updateCartRuleOptionsTable(): void
    {
        $this->addColumn(CartRuleOptionsRepository::TABLE_NAME, 'details_cms_id', 'int(10) unsigned');
        $this->addForeignKey(CartRuleOptionsRepository::TABLE_NAME, 'cms', ['details_cms_id'], ['id_cms'], CartRuleOptionsRepository::TABLE_NAME . '-details_cms_id', [
            'onDelete' => 'SET NULL',
        ]);
    }
}
