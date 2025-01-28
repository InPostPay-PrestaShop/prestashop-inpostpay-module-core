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
        } catch (\PrestaShopException $e) {
            if (!$e->getPrevious() instanceof \PDOException) {
                throw $e;
            }

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
            return $this->db->execute($sql);
        });

        return (int) $this->db->Affected_Rows();
    }

    /**
     * @param string $sql
     *
     * @return array<string, mixed>|false
     */
    public function fetchAssociative(string $sql)
    {
        return $this->execute(function () use ($sql) {
            return $this->db->getRow($sql);
        });
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

    /**
     * @param array<string, mixed> $data column-value pairs
     * @param array<string|int, mixed> $criteria update criteria
     *
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, array $criteria = []): int
    {
        $this->execute(function () use ($table, $data, $criteria) {
            return $this->db->update(
                $table,
                $data,
                $this->getWhereConditions($criteria),
                0,
                true
            );
        });

        return (int) $this->db->Affected_Rows();
    }

    private function normalizeException(\PrestaShopException $e): \PrestaShopDatabaseException
    {
        $previous = $e->getPrevious();
        $errorCode = $previous instanceof \PDOException
            ? $previous->errorInfo[1] ?? $this->db->getNumberError()
            : $this->db->getNumberError();

        return new \PrestaShopDatabaseException($e->getMessage(), $errorCode, $e);
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function getWhereConditions(array $criteria): string
    {
        if ([] === $criteria) {
            return '';
        }

        $conditions = [];

        foreach ($criteria as $key => $value) {
            if (is_int($key)) {
                // $value should be raw SQL
                $conditions[] = (string) $value;

                continue;
            }

            $column = bqSQL($key);
            if (null === $value) {
                $conditions[] = sprintf('`%s` IS NULL', $column);
            } else {
                $conditions[] = sprintf('`%s` = \'%s\'', $column, pSQL($value));
            }
        }

        return implode(' AND ', $conditions);
    }
}
