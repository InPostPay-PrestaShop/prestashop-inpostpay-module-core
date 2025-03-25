<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Event;

use izi\prestashop\Event\Event;

final class StockQuantityUpdatedEvent extends Event
{
    /**
     * @var int
     */
    private $productId;

    /**
     * @var int
     */
    private $combinationId;

    /**
     * @var int
     */
    private $shopId;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var int|null
     */
    private $deltaQuantity;

    public function __construct(int $productId, int $combinationId, int $shopId, int $quantity, ?int $deltaQuantity = null)
    {
        $this->productId = $productId;
        $this->combinationId = $combinationId;
        $this->shopId = $shopId;
        $this->quantity = $quantity;
        $this->deltaQuantity = $deltaQuantity;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getCombinationId(): int
    {
        return $this->combinationId;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getDeltaQuantity(): ?int
    {
        return $this->deltaQuantity;
    }
}
