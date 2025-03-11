<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\ObjectModel\Entity\InPostIziBasketSession;

final class Version_2_0_0 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '2.0.0';
    }

    public function up(): void
    {
        $this->addColumn(InPostIziBasketSession::TABLE_NAME, 'binding_api_key', 'VARCHAR(255)');
        $this->dropColumn(InPostIziBasketSession::TABLE_NAME, 'basket_cache');
    }
}
