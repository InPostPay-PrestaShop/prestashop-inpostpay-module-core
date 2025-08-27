<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\HotProduct\HotProductRepository;
use izi\prestashop\PromoCode\CartRuleOptionsRepository;
use izi\prestashop\Repository\ProductRestrictionsRepository;

/**
 * @final
 */
class Version_2_1_0 extends AbstractMigration
{
    private const HP_PRODUCT_ID_FK = HotProductRepository::TABLE_NAME . '-product_id';
    private const HP_COMBINATION_ID_FK = HotProductRepository::TABLE_NAME . '-combination_id';
    private const HP_SHOP_ID_FK = HotProductRepository::TABLE_NAME . '-shop_id';
    private const HP_REFERENCE_ID_IDX = HotProductRepository::TABLE_NAME . '_reference_id_unique';
    private const HP_PRODUCT_COMBINATION_ID_IDX = HotProductRepository::TABLE_NAME . '_combination_idx';

    private const CRO_CMS_ID_FK = CartRuleOptionsRepository::TABLE_NAME . '-details_cms_id';

    private const FR_FEATURE_ID_FK = ProductRestrictionsRepository::FEATURE_RESTRICTIONS_TABLE . '-feature_id';
    private const FR_SHOP_ID_FK = ProductRestrictionsRepository::FEATURE_RESTRICTIONS_TABLE . '-shop_id';
    private const FR_FEATURE_SHOP_ID_IDX = ProductRestrictionsRepository::FEATURE_RESTRICTIONS_TABLE . '-feature_shop_uniq';

    public function getVersion(): string
    {
        return '2.1.0';
    }

    public function up(): void
    {
        $this->createHotProductsTable();
        $this->updateCartRuleOptionsTable();
        $this->createFeatureRestrictionsTable();
    }

    private function createHotProductsTable(): void
    {
        if ($this->tableExists(HotProductRepository::TABLE_NAME)) {
            return;
        }

        $this->connection->executeStatement('
            CREATE TABLE `' . _DB_PREFIX_ . HotProductRepository::TABLE_NAME . '` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` INT(11) UNSIGNED NOT NULL,
                `combination_id` INT(11) UNSIGNED,
                `shop_id` INT(11),
                `reference_id` varchar(32) NOT NULL,
                `available_from` DATETIME,
                `available_to` DATETIME,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `' . self::HP_REFERENCE_ID_IDX . '` (`reference_id`, `shop_id`),
                KEY `' . self::HP_PRODUCT_COMBINATION_ID_IDX . '` (`product_id`, `combination_id`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            CHARSET = utf8
            COLLATE = utf8_general_ci
        ');

        $this->addForeignKey(HotProductRepository::TABLE_NAME, 'product', ['product_id'], ['id_product'], self::HP_PRODUCT_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);

        $this->addForeignKey(HotProductRepository::TABLE_NAME, 'product_attribute', ['combination_id'], ['id_product_attribute'], self::HP_COMBINATION_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);

        $this->addForeignKey(HotProductRepository::TABLE_NAME, 'shop', ['shop_id'], ['id_shop'], self::HP_SHOP_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
    }

    private function updateCartRuleOptionsTable(): void
    {
        $this->addColumn(CartRuleOptionsRepository::TABLE_NAME, 'details_cms_id', 'INT(10) UNSIGNED');
        $this->addForeignKey(CartRuleOptionsRepository::TABLE_NAME, 'cms', ['details_cms_id'], ['id_cms'], self::CRO_CMS_ID_FK, [
            'onDelete' => 'SET NULL',
        ]);
    }

    private function createFeatureRestrictionsTable(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . ProductRestrictionsRepository::FEATURE_RESTRICTIONS_TABLE . '` (
                `id_feature` INT(10) UNSIGNED NOT NULL,
                `id_shop` INT(11),
                CONSTRAINT `' . self::FR_FEATURE_SHOP_ID_IDX . '` UNIQUE (`id_feature`, `id_shop`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            CHARSET = utf8
            COLLATE = utf8_general_ci;
        ');

        $this->addForeignKey(ProductRestrictionsRepository::FEATURE_RESTRICTIONS_TABLE, 'feature', ['id_feature'], ['id_feature'], self::FR_FEATURE_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);

        $this->addForeignKey(ProductRestrictionsRepository::FEATURE_RESTRICTIONS_TABLE, 'shop', ['id_shop'], ['id_shop'], self::FR_SHOP_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
    }
}
