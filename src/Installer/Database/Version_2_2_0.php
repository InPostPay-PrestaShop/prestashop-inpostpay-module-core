<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\HotProduct\HotProductRepository;
use izi\prestashop\ObjectModel\Entity\InPostIziBasketSession;
use izi\prestashop\PromoCode\CartRuleOptionsRepository;
use izi\prestashop\Repository\ProductRestrictionsRepository;

class Version_2_2_0 extends AbstractMigration
{
    private const BS_SHOP_ID_FK = InPostIziBasketSession::TABLE_NAME . '-shop_id';

    public function getVersion(): string
    {
        return '2.2.0';
    }

    public function up(): void
    {
        $this->updateBasketSessionTable();
    }

    private function updateBasketSessionTable(): void
    {
        $this->addColumn(InPostIziBasketSession::TABLE_NAME, 'id_shop', 'INT(10) DEFAULT NULL');
        $this->addForeignKey(InPostIziBasketSession::TABLE_NAME, 'shop', ['id_shop'], ['id_shop'], self::BS_SHOP_ID_FK, [
            'onDelete' => 'SET NULL',
        ]);
    }
}
