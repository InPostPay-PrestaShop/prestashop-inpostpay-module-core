<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Price;

final class CalculationParameters
{
    /**
     * @var int
     */
    private $shopId;

    /**
     * @var int
     */
    private $currencyId;

    /**
     * @var int
     */
    private $countryId;

    /**
     * @var int
     */
    private $customerGroupId;

    public function __construct(int $shopId, int $currencyId, int $countryId, int $customerGroupId)
    {
        $this->shopId = $shopId;
        $this->currencyId = $currencyId;
        $this->countryId = $countryId;
        $this->customerGroupId = $customerGroupId;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getCountryId(): int
    {
        return $this->countryId;
    }

    public function getCustomerGroupId(): int
    {
        return $this->customerGroupId;
    }
}
