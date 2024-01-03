<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface LanguageAwareConfigurationInterface extends ShopAwareConfigurationInterface
{
    public function get(string $key, int $shopId = null, int $languageId = null);

    /**
     * @return array<int, mixed> values by language ID
     */
    public function getLocalized(string $key): array;
}
