<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule\Factory;

use izi\prestashop\InPostDiscount\CartRuleDiscount;
use izi\prestashop\InPostDiscount\Exception\ZeroAmountException;
use izi\prestashop\MerchantApi\Model\Order\Request\InPostDiscount;

interface CartRuleFactoryInterface
{
    /**
     * @throws ZeroAmountException if the cart total cannot be reduced further
     */
    public function create(\Cart $cart, InPostDiscount $discount): CartRuleDiscount;
}
