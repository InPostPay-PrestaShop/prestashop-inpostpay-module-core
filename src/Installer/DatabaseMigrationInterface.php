<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

interface DatabaseMigrationInterface
{
    public function getVersion(): string;

    public function up();

    public function down();
}
