<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface ShopAwareConfigurationInterface extends ConfigurationInterface
{
    /**
     * @param int|null $shopId if null, the value corresponding to the current shop context will be returned
     *
     * @return mixed|null
     */
    public function get(string $key, ?int $shopId = null);

    /**
     * @param int|null $shopId if null, the current context shop's configuration will be updated
     * @param mixed $value
     */
    public function set(string $key, $value, ?int $shopId = null);

    /**
     * @return mixed|null
     */
    public function getGlobal(string $key);

    /**
     * @param mixed $value
     */
    public function setGlobal(string $key, $value);
}
