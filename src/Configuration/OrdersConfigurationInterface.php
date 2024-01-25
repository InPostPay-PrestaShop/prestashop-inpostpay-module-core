<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface OrdersConfigurationInterface
{
    public function getInitialStatusId(int $shopId = null): ?int;

    public function getPaidStatusId(int $shopId = null): ?int;

    /**
     * @return array<int, string> description by status ID
     */
    public function getStatusDescriptionMapping(int $languageId, int $shopId = null): array;

    public function getStatusDescription(int $statusId, int $languageId, int $shopId): ?string;

    public function isBankPaymentEnabled(int $shopId = null): bool;

    public function isCarrierPaymentEnabled(int $shopId = null): bool;

    public function getPointOfSaleId(int $shopId = null): ?string;
}
