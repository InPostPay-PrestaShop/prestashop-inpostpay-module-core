<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command\Basket;

use izi\prestashop\MerchantApi\Handler\Basket\IncrementCartQuantityHandler;

/**
 * @see IncrementCartQuantityHandler
 */
final class IncrementCartQuantityCommand
{
    /**
     * @var \Cart
     */
    private $cart;

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
    private $customizationId;

    /**
     * @var int
     */
    private $quantity;

    public function __construct(\Cart $cart, int $productId, int $combinationId, int $customizationId = 0, int $quantity = 1)
    {
        $this->cart = $cart;
        $this->productId = $productId;
        $this->combinationId = $combinationId;
        $this->customizationId = $customizationId;
        $this->quantity = $quantity;
    }

    public function getCart(): \Cart
    {
        return $this->cart;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getCombinationId(): int
    {
        return $this->combinationId;
    }

    public function getCustomizationId(): int
    {
        return $this->customizationId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
