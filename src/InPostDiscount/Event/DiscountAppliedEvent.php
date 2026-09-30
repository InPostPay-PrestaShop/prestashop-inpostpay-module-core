<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\Event;

use izi\prestashop\Event\Event;
use izi\prestashop\InPostDiscount\DiscountInterface;

final class DiscountAppliedEvent extends Event
{
    /**
     * @var \Cart
     */
    private $cart;

    /**
     * @var DiscountInterface
     */
    private $discount;

    public function __construct(\Cart $cart, DiscountInterface $discount)
    {
        $this->cart = $cart;
        $this->discount = $discount;
    }

    public function getCart(): \Cart
    {
        return $this->cart;
    }

    public function getDiscount(): DiscountInterface
    {
        return $this->discount;
    }
}
