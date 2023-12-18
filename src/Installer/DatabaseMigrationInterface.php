<?php

namespace izi\prestashop\Installer;

interface DatabaseMigrationInterface
{
    public function getVersion(): string;

    public function up(): bool;

    public function down(): bool;
}
