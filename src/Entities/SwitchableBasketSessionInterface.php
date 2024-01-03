<?php

declare(strict_types=1);

namespace izi\prestashop\Entities;

interface SwitchableBasketSessionInterface extends BasketSessionInterface
{
    public function switchBasket(BasketInterface $basket);

    public function wasBasketSwitched(): bool;
}
