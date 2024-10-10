<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\Repository\ProductRestrictionsRepository;

final class Version_1_9_0 extends AbstractMigration
{
    private const CATEGORY_ID_FK = ProductRestrictionsRepository::CATEGORY_RESTRICTIONS_TABLE . '-category_id';
    private const CATEGORY_SHOP_ID_FK = ProductRestrictionsRepository::CATEGORY_RESTRICTIONS_TABLE . '-shop_id';
    private const CATEGORY_SHOP_ID_IDX = ProductRestrictionsRepository::CATEGORY_RESTRICTIONS_TABLE . '-category_shop_uniq';

    private const MANUFACTURER_ID_FK = ProductRestrictionsRepository::MANUFACTURER_RESTRICTIONS_TABLE . '-manufacturer_id';
    private const MANUFACTURER_SHOP_ID_FK = ProductRestrictionsRepository::MANUFACTURER_RESTRICTIONS_TABLE . '-shop_id';
    private const MANUFACTURER_SHOP_ID_IDX = ProductRestrictionsRepository::MANUFACTURER_RESTRICTIONS_TABLE . '-manufacturer_shop_uniq';

    private const ATTRIBUTE_GROUP_ID_FK = ProductRestrictionsRepository::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE . '-attr_group_id';
    private const ATTRIBUTE_GROUP_SHOP_ID_FK = ProductRestrictionsRepository::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE . '-shop_id';
    private const ATTRIBUTE_GROUP_SHOP_ID_IDX = ProductRestrictionsRepository::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE . '-attr_group_shop_uniq';

    public function getVersion(): string
    {
        return '1.9.0';
    }

    public function up(): void
    {
        $this->createCategoryRestrictionsTable();
        $this->createManufacturerRestrictionsTable();
        $this->createAttributeGroupRestrictionsTable();
    }

    private function createCategoryRestrictionsTable(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . ProductRestrictionsRepository::CATEGORY_RESTRICTIONS_TABLE . '` (
                id_category INT(10) UNSIGNED NOT NULL,
                id_shop INT(11),
                CONSTRAINT `' . self::CATEGORY_SHOP_ID_IDX . '` UNIQUE (`id_category`, `id_shop`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            CHARSET = utf8
            COLLATE = utf8_general_ci;
        ');

        $this->addForeignKey(ProductRestrictionsRepository::CATEGORY_RESTRICTIONS_TABLE, 'category', ['id_category'], ['id_category'], self::CATEGORY_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
        $this->addForeignKey(ProductRestrictionsRepository::CATEGORY_RESTRICTIONS_TABLE, 'shop', ['id_shop'], ['id_shop'], self::CATEGORY_SHOP_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
    }

    private function createManufacturerRestrictionsTable(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . ProductRestrictionsRepository::MANUFACTURER_RESTRICTIONS_TABLE . '` (
                id_manufacturer INT(10) UNSIGNED NOT NULL,
                id_shop INT(11),
                CONSTRAINT `' . self::MANUFACTURER_SHOP_ID_IDX . '` UNIQUE (`id_manufacturer`, `id_shop`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            CHARSET = utf8
            COLLATE = utf8_general_ci;
        ');

        $this->addForeignKey(ProductRestrictionsRepository::MANUFACTURER_RESTRICTIONS_TABLE, 'manufacturer', ['id_manufacturer'], ['id_manufacturer'], self::MANUFACTURER_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
        $this->addForeignKey(ProductRestrictionsRepository::MANUFACTURER_RESTRICTIONS_TABLE, 'shop', ['id_shop'], ['id_shop'], self::MANUFACTURER_SHOP_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
    }

    private function createAttributeGroupRestrictionsTable(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . ProductRestrictionsRepository::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE . '` (
                id_attribute_group INT(11) NOT NULL,
                id_shop INT(11),
                CONSTRAINT `' . self::ATTRIBUTE_GROUP_SHOP_ID_IDX . '` UNIQUE (`id_attribute_group`, `id_shop`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            CHARSET = utf8
            COLLATE = utf8_general_ci;
        ');

        $this->addForeignKey(ProductRestrictionsRepository::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE, 'attribute_group', ['id_attribute_group'], ['id_attribute_group'], self::ATTRIBUTE_GROUP_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
        $this->addForeignKey(ProductRestrictionsRepository::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE, 'shop', ['id_shop'], ['id_shop'], self::ATTRIBUTE_GROUP_SHOP_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
    }
}
