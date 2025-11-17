<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\ProductOptions\ProductOptionsRepository;

final class Version_2_4_0 extends AbstractMigration
{
    private const PO_PRODUCT_ID_FK = ProductOptionsRepository::TABLE_NAME . '-product_id';

    public function getVersion(): string
    {
        return '2.4.0';
    }

    public function up(): void
    {
        $this->createProductOptionsTable();
    }

    private function createProductOptionsTable(): void
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . ProductOptionsRepository::TABLE_NAME . '` (
                `product_id` INT(10) UNSIGNED NOT NULL,
                `image_gallery_type` TINYINT(1) UNSIGNED,
                PRIMARY KEY (`product_id`)
            )
            ENGINE = ' . _MYSQL_ENGINE_ . '
            CHARSET = utf8
            COLLATE = utf8_general_ci;
        ');

        $this->addForeignKey(ProductOptionsRepository::TABLE_NAME, 'product', ['product_id'], ['id_product'], self::PO_PRODUCT_ID_FK, [
            'onDelete' => 'CASCADE',
        ]);
    }
}
