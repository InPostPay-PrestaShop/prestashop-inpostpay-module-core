<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\Adapter;

use izi\prestashop\Configuration\LanguageAwareConfigurationInterface;

final class Configuration implements LanguageAwareConfigurationInterface
{
    /**
     * @var \Db
     */
    private $db;

    public function __construct(?\Db $db = null)
    {
        $this->db = $db ?? \Db::getInstance();
    }

    public function get(string $key, ?int $shopId = null, ?int $languageId = null)
    {
        return $this->doGet($key, $languageId, $shopId);
    }

    public function getGlobal(string $key)
    {
        return $this->doGet($key, null, 0, 0);
    }

    public function getLocalized(string $key): array
    {
        $configuration = [];

        foreach (\Language::getIDs(false) as $languageId) {
            $configuration[$languageId] = $this->doGet($key, (int) $languageId);
        }

        return $configuration;
    }

    public function set(string $key, $value, ?int $shopId = null): void
    {
        $this->doSet($key, $value, $shopId);
    }

    public function setGlobal(string $key, $value): void
    {
        $this->doSet($key, $value, 0, 0);
    }

    public function remove(string $key): void
    {
        if (\Configuration::deleteByName($key)) {
            return;
        }

        throw new \RuntimeException(sprintf('Could not remove configuration values for key "%s".', $key));
    }

    public function has(string $key): bool
    {
        return \Configuration::hasKey($key);
    }

    public function isValidKey(string $key): bool
    {
        return strlen($key) <= \Configuration::$definition['fields']['name']['size']
            && \Validate::isConfigName($key);
    }

    public function removeMatching(string $keyPattern): void
    {
        $keyPattern = str_replace('_', '#_', pSQL($keyPattern));
        $keyPatterns = $this->getSqlKeyPatterns($keyPattern);

        $result = $this->db->delete('configuration', implode(' AND ', array_map(static function (string $pattern): string {
            return 'name LIKE "' . $pattern . '" ESCAPE "#"';
        }, $keyPatterns)));

        if (false === $result) {
            throw new \RuntimeException(sprintf('Could not remove configuration values matching pattern "%s".', $keyPattern));
        }
    }

    private function getSqlKeyPatterns(string $pattern): array
    {
        $parts = preg_split('/\*+/', $pattern, 0);

        foreach ($parts as $key => $part) {
            if ('' !== $part) {
                continue;
            }

            if (isset($parts[$key - 1])) {
                $parts[$key - 1] .= '%';
            }

            if (isset($parts[$key + 1])) {
                $parts[$key + 1] = '%' . $parts[$key + 1];
            }

            unset($parts[$key]);
        }

        return $parts;
    }

    private function doGet(string $key, ?int $languageId = null, ?int $shopId = null, ?int $shopGroupId = null)
    {
        $value = \Configuration::get($key, $languageId, $shopGroupId, $shopId, null);

        return '' === $value ? null : $value;
    }

    private function doSet(string $key, $value, ?int $shopId = null, ?int $shopGroupId = null): void
    {
        if (\Configuration::updateValue($key, $value, false, $shopGroupId, $shopId)) {
            return;
        }

        throw new \RuntimeException(sprintf('Could not update the configuration value for key "%s".', $key));
    }
}
