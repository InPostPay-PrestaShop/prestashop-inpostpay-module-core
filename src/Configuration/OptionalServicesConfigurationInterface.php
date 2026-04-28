<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface OptionalServicesConfigurationInterface
{
    public function isServiceEnabled(string $serviceCode, ?int $shopId = null): bool;

    /**
     * @return string[]
     */
    public function getDisabledServiceCodes(?int $shopId = null): array;
}
