<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

/**
 * @method persist(object $configuration)
 */
interface PersistentConfigurationInterface
{
    /**
     * @return mixed in memory representation of the configuration settings
     */
    public function copy();
}
