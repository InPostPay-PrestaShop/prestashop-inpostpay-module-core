<?php

declare(strict_types=1);

namespace izi\prestashop\Module;

class ModuleRepository
{
    /**
     * @template T of \Module
     *
     * @param class-string<T> $name
     *
     * @return T|null
     */
    public function findByName(string $name): ?\Module
    {
        if (false === $module = \Module::getInstanceByName($name)) {
            return null;
        }

        return $module;
    }
}
