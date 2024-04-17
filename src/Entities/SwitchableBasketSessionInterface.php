<?php

declare(strict_types=1);

namespace izi\prestashop\Entities;

/**
 * @template T of BasketInterface
 *
 * @template-extends BasketSessionInterface<T>
 */
interface SwitchableBasketSessionInterface extends BasketSessionInterface
{
    public function switchBasket(BasketInterface $basket);

    public function wasBasketSwitched(): bool;
}
