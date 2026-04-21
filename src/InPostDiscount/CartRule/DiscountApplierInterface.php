<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule;

use izi\prestashop\InPostDiscount\CartRuleDiscount;
use PrestaShop\PrestaShop\Core\Cart\CartRuleCalculator;
use PrestaShop\PrestaShop\Core\Cart\CartRuleData;

interface DiscountApplierInterface
{
    public function apply(CartRuleCalculator $calculator, CartRuleData $data, CartRuleDiscount $discount, bool $withShipping): void;
}
