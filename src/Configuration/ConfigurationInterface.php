<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface ConfigurationInterface
{
    /**
     * @return mixed|null
     */
    public function get(string $key);

    /**
     * @param mixed $value
     */
    public function set(string $key, $value);

    public function remove(string $key);

    public function has(string $key): bool;

    public function isValidKey(string $key): bool;

    /**
     * @param string $keyPattern config key pattern with "*" as a wildcard
     */
    public function removeMatching(string $keyPattern);
}
