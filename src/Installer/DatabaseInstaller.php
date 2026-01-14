<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\ShopAwareConfigurationInterface;
use izi\prestashop\Installer\Database\MigrationInterface;
use izi\prestashop\Installer\Exception\InstallerException;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DatabaseInstaller implements InstallerInterface
{
    private const SCHEMA_VERSION_CONFIG_KEY = 'INPOST_PAY_DB_SCHEMA_VERSION';

    /**
     * @var iterable<MigrationInterface>
     */
    private $migrations;

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    /**
     * @var TranslatorInterface|IdentityTranslator
     */
    private $translator;

    /**
     * @param iterable<MigrationInterface> $migrations
     */
    public function __construct(iterable $migrations, ?ShopAwareConfigurationInterface $configuration = null, ?TranslatorInterface $translator = null)
    {
        $this->migrations = $migrations;
        $this->configuration = $configuration ?? new Configuration();
        $this->translator = $translator ?? new IdentityTranslator();
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

        usort($migrations, static function (MigrationInterface $m1, MigrationInterface $m2): int {
            return version_compare($m1->getVersion(), $m2->getVersion());
        });

        return $migrations;
    }

    private function migrateUp(MigrationInterface $migration): void
    {
        try {
            $migration->up();
            $this->updateSchemaVersion($migration->getVersion());
        } catch (\Exception $e) {
            throw new InstallerException($this->translator->trans('Could not update the database schema.', [], 'Modules.Inpostizi.Installer'), 0, $e);
        }
    }

    private function updateSchemaVersion(?string $version): void
    {
        $this->configuration->setGlobal(self::SCHEMA_VERSION_CONFIG_KEY, $version);
    }
}
