<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel;

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

    public function save(\ObjectModel $model)
    {
        return $this->execute(function () use ($model) {
            return $model->save();
        });
    }

    public function delete(\ObjectModel $model)
    {
        return $this->execute(function () use ($model) {
            return $model->delete();
        });
    }

    public function fetchAllAssociative(string $sql): array
    {
        return $this->execute(function () use ($sql) {
            return $this->db->executeS($sql);
        });
    }

    /**
     * @template T
     *
     * @param \Closure(): T $closure
     *
     * @return T passed function result
     *
     * @throws \PrestaShopDatabaseException
     */
    protected function execute(\Closure $closure)
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

    private function normalizeException(\PrestaShopDatabaseException $e): \PrestaShopDatabaseException
    {
        return new \PrestaShopDatabaseException($e->getMessage(), $this->db->getNumberError());
    }
}
