<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\ShopAwareConfigurationInterface;
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

    public function install(\Module $module): bool
    {
        $currentVersion = $this->configuration->getGlobal(self::SCHEMA_VERSION_CONFIG_KEY) ?: '0';

        foreach ($this->getSortedMigrations() as $migration) {
            if (\Tools::version_compare($currentVersion, $version = $migration->getVersion(), '>=')) {
                continue;
            }

            if (\Tools::version_compare($module->version, $version)) {
                return true;
            }

            if (!$this->migrateUp($migration)) {
                return false;
            }

            $currentVersion = $version;
        }

        return true;
    }

    private function getSortedMigrations(): array
    {
        $migrations = $this->migrations;
        if ($migrations instanceof \Traversable) {
            $migrations = iterator_to_array($migrations);
        }

        usort($migrations, static function (DatabaseMigrationInterface $m1, DatabaseMigrationInterface $m2): int {
            return \Tools::version_compare($m1->getVersion(), $m2->getVersion(), null);
        });

        return $migrations;
    }

    private function migrateUp(DatabaseMigrationInterface $migration): bool
    {
        try {
            if (!$migration->up()) {
                return false;
            }

            $this->updateSchemaVersion($migration->getVersion());
        } catch (\Exception $e) {
            return false;
        }

        return true;
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
        $db = \Db::getInstance();

        return [
            new Version_1_4_0($db),
            new Version_1_9_0($db),
        ];
    }
}
