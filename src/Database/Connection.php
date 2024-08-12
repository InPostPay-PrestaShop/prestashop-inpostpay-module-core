<?php

declare(strict_types=1);

namespace izi\prestashop\Database;

/**
 * @experimental
 */
class Connection
{
    protected $db;

    public function __construct(?\Db $db = null)
    {
        $this->db = $db ?? \Db::getInstance();
    }

    public function getPlatformVersion(): string
    {
        return $this->db->getVersion();
    }

    /**
     * @template T
     *
     * @param \Closure(): T $closure function returning false on errors
     *
     * @return T value returned by $closure
     *
     * @throws \PrestaShopDatabaseException if $closure failed due to a DB error
     */
    public function execute(\Closure $closure)
    {
        try {
            $result = $closure();
        } catch (\PrestaShopDatabaseException $e) {
            throw $this->normalizeException($e);
        }

        if (false !== $result || !$error = $this->db->getNumberError()) {
            return $result;
        }

        throw new \PrestaShopDatabaseException($this->db->getMsgError(), $error);
    }

    /**
     * @return int number of affected rows
     *
     * @throws \PrestaShopDatabaseException on failure
     */
    public function executeStatement(string $sql): int
    {
        $this->execute(function () use ($sql) {
            $this->db->execute($sql);
        });

        return (int) $this->db->numRows();
    }

    public function fetchAllAssociative(string $sql): array
    {
        return $this->execute(function () use ($sql) {
            return $this->db->executeS($sql);
        });
    }

    public function fetchFirstColumn(string $sql): array
    {
        $result = $this->execute(function () use ($sql) {
            return $this->db->query($sql);
        });

        $rows = [];

        while (false !== $row = $this->db->nextRow($result)) {
            $rows[] = array_shift($row);
        }

        return $rows;
    }

    /**
     * @return mixed|false
     */
    public function fetchOne(string $sql)
    {
        return $this->execute(function () use ($sql) {
            return $this->db->getValue($sql);
        });
    }

    public function insert(string $table, array $data): void
    {
        $this->execute(function () use ($table, $data) {
            return $this->db->insert($table, $data, true);
        });
    }

    private function normalizeException(\PrestaShopDatabaseException $e): \PrestaShopDatabaseException
    {
        return new \PrestaShopDatabaseException($e->getMessage(), $this->db->getNumberError());
    }
}
