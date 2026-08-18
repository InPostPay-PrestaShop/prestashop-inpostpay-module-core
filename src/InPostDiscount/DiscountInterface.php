<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount;

interface DiscountInterface
{
    public function getCartId(): int;

    public function getType(): string;

    public function getAmount(): DiscountAmount;
}
