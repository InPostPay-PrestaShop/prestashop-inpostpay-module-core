<?php

declare(strict_types=1);

namespace izi\prestashop\Entities;

/**
 * @implements BasketInterface<\Cart>
 */
final class Cart implements BasketInterface
{
    private $cart;

    public function __construct(\Cart $cart)
    {
        $this->cart = $cart;
    }

    public function getId(): int
    {
        return (int) $this->cart->id;
    }

    public function getEntity(): \Cart
    {
        return $this->cart;
    }
}
