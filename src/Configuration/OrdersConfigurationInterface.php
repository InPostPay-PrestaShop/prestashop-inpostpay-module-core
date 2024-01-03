<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface OrdersConfigurationInterface
{
    public function getPaidOrderStatusId(int $shopId = null): int;

    /**
     * @return array<int, string> description by status ID
     */
    public function getOrderStatusDescriptionMapping(int $languageId, int $shopId = null): array;

    public function getOrderStatusDescription(int $statusId, int $languageId, int $shopId): ?string;
}
