<?php

declare(strict_types=1);

namespace x13pricehistory\Providers;

abstract class BatchLowestPriceProvider
{
    abstract public function getPricesForProductList(array $productIds, ?int $shopId = null, ?int $currencyId = null, ?int $countryId = null, ?int $customerGroupId = null, bool $useTax = true): array;
}
