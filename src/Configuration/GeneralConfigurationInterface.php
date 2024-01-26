<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface GeneralConfigurationInterface
{
    public function isEnabledForEveryone(): bool;

    public function getMaxSuggestedProducts(int $shopId = null): ?int;
}
