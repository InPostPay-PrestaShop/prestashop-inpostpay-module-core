<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

interface PrestaShopVersionAwareHookInterface extends HookInterface
{
    public static function getVersionRange(): VersionRange;
}
