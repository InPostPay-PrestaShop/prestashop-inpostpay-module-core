<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\PaymentType;

/**
 * @method PaymentType[] getAvailablePaymentOptions(int $shopId = null)
 */
interface OrdersConfigurationInterface
{
    public function getInitialStatusId(?int $shopId = null): ?int;

    public function getPaidStatusId(?int $shopId = null): ?int;

    /**
     * @return array<int, array<int, string>> descriptions by language and status ID
     */
    public function getStatusDescriptionMap(): array;

    public function getStatusDescription(int $statusId, int $languageId, int $shopId): ?string;

    /**
     * @deprecated use {@see self::getAvailablePaymentOptions()} instead
     */
    public function isBankPaymentEnabled(): bool;

    /**
     * @deprecated use {@see self::getAvailablePaymentOptions()} instead
     */
    public function isCarrierPaymentEnabled(): bool;

    public function getPointOfSaleId(?int $shopId = null): ?string;
}
