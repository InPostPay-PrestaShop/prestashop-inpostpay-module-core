<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

/**
 * @template T of object
 *
 * @mixin T
 *
 * @method persist(object $configuration)
 */
interface PersistentConfigurationInterface
{
    /**
     * @return T in memory representation of the configuration settings
     */
    public function copy();
}
