<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\ShopAwareConfigurationInterface;
use izi\prestashop\Database\Connection;
use izi\prestashop\Installer\Database\Version_1_4_0;
use izi\prestashop\Installer\Database\Version_1_9_0;

final class DatabaseInstaller
{
    private const SCHEMA_VERSION_CONFIG_KEY = 'INPOST_PAY_DB_SCHEMA_VERSION';

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    /**
     * @var iterable<DatabaseMigrationInterface>
     */
    private $migrations;

    /**
     * @param iterable<DatabaseMigrationInterface>|null $migrations
     */
    public function __construct(?ShopAwareConfigurationInterface $configuration = null, ?iterable $migrations = null)
    {
        $this->configuration = $configuration ?? new Configuration();
        $this->migrations = $migrations ?? $this->getDefaultMigrations();
    }

    public function install(\Module $module): void
    {
        $currentVersion = $this->configuration->getGlobal(self::SCHEMA_VERSION_CONFIG_KEY) ?: '0';

        foreach ($this->getSortedMigrations() as $migration) {
            if (\Tools::version_compare($currentVersion, $version = $migration->getVersion(), '>=')) {
                continue;
            }

            if (\Tools::version_compare($module->version, $version)) {
                return;
            }

            $this->migrateUp($migration);

            $currentVersion = $version;
        }
    }

    private function getSortedMigrations(): array
    {
        $migrations = $this->migrations;
        if ($migrations instanceof \Traversable) {
            $migrations = iterator_to_array($migrations);
        }

        usort($migrations, static function (DatabaseMigrationInterface $m1, DatabaseMigrationInterface $m2): int {
            return version_compare($m1->getVersion(), $m2->getVersion());
        });

        return $migrations;
    }

    private function migrateUp(DatabaseMigrationInterface $migration): void
    {
        $migration->up();
        $this->updateSchemaVersion($migration->getVersion());
    }

    private function updateSchemaVersion(?string $version): void
    {
        $this->configuration->setGlobal(self::SCHEMA_VERSION_CONFIG_KEY, $version);
    }

    /**
     * @return DatabaseMigrationInterface[]
     */
    private function getDefaultMigrations(): array
    {
        $connection = new Connection(\Db::getInstance());

        return [
            new Version_1_4_0($connection),
            new Version_1_9_0($connection),
        ];
    }
}
