<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Database;

use izi\prestashop\Database\Connection;
use izi\prestashop\Installer\DatabaseMigrationInterface;

abstract class AbstractMigration implements DatabaseMigrationInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(?Connection $connection = null)
    {
        $this->connection = $connection ?? new Connection(\Db::getInstance());
    }

    public function down(): void
    {
    }

    protected function tableExists(string $table): bool
    {
        return (bool) $this->connection->fetchOne('SELECT EXISTS (
            SELECT *
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = "' . _DB_PREFIX_ . pSQL($table) . '" 
        )');
    }

    protected function dropTable(string $table): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . pSQL($table) . '`');
    }

    protected function dropColumn(string $table, string $column): void
    {
        if (!$this->columnExists($table, $column)) {
            return;
        }

        $this->connection->executeStatement('
            ALTER TABLE `' . _DB_PREFIX_ . pSQL($table) . '`
            DROP COLUMN `' . pSQL($column) . '`
        ');
    }

    protected function addColumn(string $table, string $column, string $definition): void
    {
        if ($this->columnExists($table, $column)) {
            return;
        }

        $this->connection->executeStatement('
            ALTER TABLE `' . _DB_PREFIX_ . pSQL($table) . '`
            ADD `' . pSQL($column) . '` ' . pSQL($definition)
        );
    }

    public function changeColumn(string $table, string $column, string $definition, ?string $newName = null): void
    {
        $column = pSQL($column);
        $newName = null !== $newName ? pSQL($newName) : $column;

        $this->connection->executeStatement('
            ALTER TABLE `' . _DB_PREFIX_ . pSQL($table) . '`
            CHANGE `' . $column . '` `' . $newName . '` ' . pSQL($definition)
        );
    }

    protected function columnExists(string $table, string $column): bool
    {
        return (bool) $this->connection->fetchOne('SELECT EXISTS (
            SELECT *
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = "' . _DB_PREFIX_ . pSQL($table) . '" 
                AND COLUMN_NAME = "' . pSQL($column) . '"
        )');
    }

    protected function addForeignKey(string $table, string $foreignTable, array $localColumnNames, array $foreignColumnNames, string $name, array $options = []): void
    {
        if ('MyISAM' === _MYSQL_ENGINE_) {
            return;
        }

        if ($this->foreignKeyExists($table, $name)) {
            return;
        }

        $sql = '
            ALTER TABLE `' . _DB_PREFIX_ . pSQL($table) . '`
            ADD CONSTRAINT `' . pSQL($name) . '`
                FOREIGN KEY (`' . implode('`,`', array_map('pSQL', $localColumnNames)) . '`)
                REFERENCES `' . _DB_PREFIX_ . pSQL($foreignTable) . '` (`' . implode('`,`', array_map('pSQL', $foreignColumnNames)) . '`)
                ' . $this->getForeignKeyOptionsSql($options);

        try {
            $this->connection->executeStatement($sql);
        } catch (\PrestaShopDatabaseException $e) {
            if (150 === $e->getCode()) {
                // ignore silently: possible column types mismatch (e.g. due to migration from earlier PS versions) or the referenced table uses MyISAM engine
                return;
            }

            throw $e;
        }
    }

    protected function foreignKeyExists(string $table, string $name): bool
    {
        return (bool) $this->connection->fetchOne('SELECT EXISTS (
            SELECT *
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
                AND CONSTRAINT_TYPE = "FOREIGN KEY"
                AND CONSTRAINT_NAME = "' . pSQL($name) . '"
                AND TABLE_NAME = "' . _DB_PREFIX_ . pSQL($table) . '"
        )');
    }

    protected function addUniqueIndex(string $table, array $columns, string $name): void
    {
        if ($this->indexExists($table, $name)) {
            return;
        }

        $this->connection->executeStatement('
            ALTER TABLE `' . _DB_PREFIX_ . pSQL($table) . '`
            ADD UNIQUE `' . pSQL($name) . '` (`' . implode('`,`', array_map('pSQL', $columns)) . '`)
        ');
    }

    protected function indexExists(string $table, string $name): bool
    {
        $result = $this->connection->fetchAllAssociative('
            SHOW INDEX
            FROM `' . _DB_PREFIX_ . pSQL($table) . '`
            WHERE Key_name = "' . pSQL($name) . '"
        ');

        return [] !== $result;
    }

    private function getForeignKeyOptionsSql(array $options): string
    {
        $sql = '';

        if (isset($options['onUpdate'])) {
            $sql .= ' ON UPDATE ' . $options['onUpdate'];
        }

        if (isset($options['onDelete'])) {
            $sql .= ' ON DELETE ' . $options['onDelete'];
        }

        return $sql;
    }
}
