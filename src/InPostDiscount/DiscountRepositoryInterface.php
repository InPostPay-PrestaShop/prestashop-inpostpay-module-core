<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount;

/**
 * @template T of DiscountInterface
 */
interface DiscountRepositoryInterface
{
    /**
     * @param T $discount
     */
    public function add(DiscountInterface $discount): void;

    public function remove(DiscountInterface $discount): void;

    /**
     * @return T[]
     */
    public function findByCartId(int $cartId): array;
}
