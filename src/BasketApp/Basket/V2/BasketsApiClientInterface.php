<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\V2;

use izi\prestashop\BasketApp\Basket\BasketsApiClientInterface as V1Client;
use izi\prestashop\BasketApp\Basket\Request\Basket;
use izi\prestashop\BasketApp\Basket\V2\Response\BasketBindingKeyResponse;
use izi\prestashop\BasketApp\Basket\V2\Response\UpdateBasketResponse;
use izi\prestashop\BasketApp\Exception\BasketExpiredException;
use izi\prestashop\BasketApp\Exception\BasketNotFoundException;

interface BasketsApiClientInterface extends V1Client
{
    /**
     * @throws BasketNotFoundException
     * @throws BasketExpiredException
     */
    public function updateBasket(string $basketId, Basket $basket): UpdateBasketResponse;

    public function initializeBasketBinding(string $basketId): BasketBindingKeyResponse;
}
