<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\PaymentType;

interface OrdersConfigurationInterface
{
    /**
     * @param PaymentType|null $paymentType if null, returns a default order status for unspecified payment option
     */
    public function getInitialStatusId(?PaymentType $paymentType = null, ?int $shopId = null): ?int;

    public function getPaidStatusId(?int $shopId = null): ?int;

    /**
     * @return array<int, array<int, string>> descriptions by language and status ID
     */
    public function getStatusDescriptionMap(): array;

    public function getStatusDescription(int $statusId, int $languageId, int $shopId): ?string;

    public function getPointOfSaleId(?int $shopId = null): ?string;

    /**
     * @return PaymentType[]
     */
    public function getAvailablePaymentOptions(?int $shopId = null): array;

    public function getMessageFormat(?int $shopId = null): string;
}
