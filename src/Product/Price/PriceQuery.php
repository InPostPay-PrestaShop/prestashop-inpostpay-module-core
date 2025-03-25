<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Price;

use izi\prestashop\Common\Currency;

final class PriceQuery
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
     * @var Currency
     */
    private $currency;

    /**
     * @var int|null
     */
    private $combinationId;

    public function __construct(int $productId, int $shopId, ?Currency $currency = null, ?int $combinationId = null)
    {
        $this->productId = $productId;
        $this->shopId = $shopId;
        $this->currency = $currency ?? Currency::getDefault();
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

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getCombinationId(): ?int
    {
        return $this->combinationId;
    }
}
