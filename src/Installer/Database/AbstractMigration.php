<?php

namespace izi\prestashop\Installer\Database;

use izi\prestashop\Installer\DatabaseMigrationInterface;

abstract class AbstractMigration implements DatabaseMigrationInterface
{
    protected $db;

    public function __construct(\Db $db = null)
    {
        $this->db = $db ?? \Db::getInstance();
    }

    public function down(): bool
    {
        return true;
    }

    protected function tableExists(string $table): bool
    {
        return (bool) $this->db->getValue('SELECT EXISTS (
            SELECT *
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = "' . _DB_PREFIX_ . pSQL($table) . '" 
        )');
    }

    protected function dropTable(string $table): bool
    {
        return $this->db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . pSQL($table) . '`');
    }

    protected function dropColumn(string $table, string $column): bool
    {
        if (!$this->columnExists($table, $column)) {
            return true;
        }

        return $this->db->execute('
            ALTER TABLE `' . _DB_PREFIX_ . pSQL($table) . '`
            DROP COLUMN `' . pSQL($column) . '`
        ');
    }

    protected function addColumn(string $table, string $column, string $definition): bool
    {
        if ($this->columnExists($table, $column)) {
            return true;
        }

        return $this->db->execute('
            ALTER TABLE `' . _DB_PREFIX_ . pSQL($table) . '`
            ADD `' . pSQL($column) . '` ' . pSQL($definition)
        );
    }

    public function changeColumn(string $table, string $column, string $definition): bool
    {
        $column = pSQL($column);

        return $this->db->execute('
            ALTER TABLE `' . _DB_PREFIX_ . pSQL($table) . '`
            CHANGE `' . $column . '` `' . $column . '` ' . pSQL($definition)
        );
    }

    protected function columnExists(string $table, string $column): bool
    {
        return (bool) $this->db->getValue('SELECT EXISTS (
            SELECT *
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = "' . _DB_PREFIX_ . pSQL($table) . '" 
                AND COLUMN_NAME = "' . pSQL($column) . '"
        )');
    }

    protected function addForeignKey(string $table, string $foreignTable, array $localColumnNames, array $foreignColumnNames, string $name, array $options = []): bool
    {
        if ($this->foreignKeyExists($table, $name)) {
            return true;
        }

        return $this->db->execute('
            ALTER TABLE `' . _DB_PREFIX_ . pSQL($table) . '`
            ADD CONSTRAINT `' . pSQL($name) . '`
                FOREIGN KEY (`' . implode('`,`', array_map('pSQL', $localColumnNames)) . '`)
                REFERENCES `' . _DB_PREFIX_ . pSQL($foreignTable) . '` (`' . implode('`,`', array_map('pSQL', $foreignColumnNames)) . '`)
                ' . $this->getForeignKeyOptionsSql($options)
        );
    }

    protected function foreignKeyExists(string $table, string $name): bool
    {
        return (bool) $this->db->getValue('SELECT EXISTS (
            SELECT *
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
                AND CONSTRAINT_TYPE = "FOREIGN KEY"
                AND CONSTRAINT_NAME = "' . pSQL($name) . '"
                AND TABLE_NAME = "' . _DB_PREFIX_ . pSQL($table) . '"
        )');
    }

    protected function addUniqueIndex(string $table, array $columns, string $name): bool
    {
        if ($this->indexExists($table, $name)) {
            return true;
        }

        return $this->db->execute('
            ALTER TABLE `' . _DB_PREFIX_ . pSQL($table) . '`
            ADD UNIQUE `' . pSQL($name) . '` (`' . implode('`,`', array_map('pSQL', $columns)) . '`)
        ');
    }

    protected function indexExists(string $table, string $name): bool
    {
        $result = $this->db->executeS('
            SHOW INDEX
            FROM `' . _DB_PREFIX_ . pSQL($table) . '`
            WHERE Key_name = "' . pSQL($name) . '"
        ');

        return false !== $result && [] !== $result;
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
