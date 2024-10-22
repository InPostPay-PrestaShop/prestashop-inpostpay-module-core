<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\PaymentType;

/**
 * @method PaymentType[] getAvailablePaymentOptions(int $shopId = null)
 * @method string getMessageFormat(int $shopId = null)
 */
interface OrdersConfigurationInterface
{
    /**
     * @param PaymentType|null $paymentType if null, returns a default order status for unspecified payment option
     */
    public function getInitialStatusId($paymentType = null/*, ?int $shopId = null*/): ?int;

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
