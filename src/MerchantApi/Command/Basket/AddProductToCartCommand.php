<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command\Basket;

use izi\prestashop\MerchantApi\Handler\Basket\AddProductToCartHandler;

/**
 * Add a product to the given cart.
 *
 * @see AddProductToCartHandler
 */
final class AddProductToCartCommand
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
     * @var int|null
     */
    private $combinationId;

    /**
     * @var int|null
     */
    private $quantity;

    /**
     * @param int|null $combinationId if null and product has combinations, the default combination should be added
     * @param int|null $quantity if null, minimal product quantity should be added
     */
    public function __construct(\Cart $cart, int $productId, ?int $combinationId = null, ?int $quantity = null)
    {
        $this->cart = $cart;
        $this->productId = $productId;
        $this->combinationId = $combinationId;
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

    public function getCombinationId(): ?int
    {
        return $this->combinationId;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }
}
