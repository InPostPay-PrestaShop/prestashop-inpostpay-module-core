<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

interface AliasedHookInterface extends HookInterface
{
    /**
     * @return array<string, VersionRange|null>
     */
    public static function getAliases(): array;
}
