<?php

declare(strict_types=1);

namespace izi\prestashop\Entities;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @implements BasketInterface<\Cart>
 */
final class CartProxy implements BasketInterface
{
    /**
     * @var int
     */
    private $cartId;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    /**
     * @var \Cart
     */
    private $cart;

    public function __construct(int $cartId, ObjectManagerInterface $manager)
    {
        $this->cartId = $cartId;
        $this->manager = $manager;
    }

    public function getId(): int
    {
        return $this->cartId;
    }

    public function getEntity(): \Cart
    {
        return $this->cart ?? ($this->cart = $this->getCart());
    }

    private function getCart(): \Cart
    {
        return $this->manager
            ->getRepository(\Cart::class)
            ->find($this->cartId);
    }
}
