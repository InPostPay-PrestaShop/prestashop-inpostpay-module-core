<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface GeneralConfigurationInterface
{
    public function isEnabledForEveryone(): bool;

    public function getMaxSuggestedProducts(int $shopId = null): ?int;

    public function getThankYouDisplayHook(int $shopId = null): ?string;

    public function getProductCardDisplayHook(int $shopId = null): ?string;
    
    public function getCheckoutButtonDisplayHook(int $shopId = null): ?string;
}
