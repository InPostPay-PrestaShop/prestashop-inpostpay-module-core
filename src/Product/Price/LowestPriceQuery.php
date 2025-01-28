<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Price;

final class LowestPriceQuery
{
    /**
     * @var int
     */
    private $productId;

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

    /**
     * @var int|null
     */
    private $combinationId;

    public function __construct(int $productId, int $shopId, int $currencyId, int $countryId, int $customerGroupId, ?int $combinationId = null)
    {
        $this->productId = $productId;
        $this->shopId = $shopId;
        $this->currencyId = $currencyId;
        $this->countryId = $countryId;
        $this->customerGroupId = $customerGroupId;
        $this->combinationId = $combinationId;
    }

    public function getProductId(): int
    {
        return $this->productId;
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

    public function getCombinationId(): ?int
    {
        return $this->combinationId;
    }
}
