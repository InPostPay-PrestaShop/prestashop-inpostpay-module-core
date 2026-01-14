<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

interface MigrationInterface
{
    public function getVersion(): string;

    public function up();

    public function down();
}
