<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount;

use izi\prestashop\InPostDiscount\Exception\UnsupportedTypeException;
use izi\prestashop\MerchantApi\Model\Order\Request\InPostDiscount;

/**
 * @template T of DiscountInterface
 */
interface DiscountHandlerInterface
{
    /**
     * @return T|null
     *
     * @throws UnsupportedTypeException
     */
    public function apply(\Cart $cart, InPostDiscount $discount): ?DiscountInterface;

    /**
     * @param T $discount
     */
    public function remove(\Cart $cart, DiscountInterface $discount): void;
}
