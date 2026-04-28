<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\Analytics\BasketAnalyticsRepository;

final class Version_2_6_0 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '2.6.0';
    }

    public function up(): void
    {
        $this->addColumn(BasketAnalyticsRepository::TABLE_NAME, 'ttclid', 'varchar(512) DEFAULT NULL');
    }

    public function down(): void
    {
        $this->dropColumn(BasketAnalyticsRepository::TABLE_NAME, 'ttclid');
    }
}
