<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket;

use izi\prestashop\BasketApp\Basket\Request\Basket;
use izi\prestashop\BasketApp\Basket\Response\BasketBindingKeyResponse;
use izi\prestashop\BasketApp\Basket\Response\BasketBindingResponse;
use izi\prestashop\BasketApp\Basket\Response\UpdateBasketResponse;
use izi\prestashop\BasketApp\Exception\BasketExpiredException;
use izi\prestashop\BasketApp\Exception\BasketNotBoundException;
use izi\prestashop\BasketApp\Exception\BasketNotFoundException;

interface BasketsApiClientInterface
{
    /**
     * @throws BasketNotFoundException
     * @throws BasketExpiredException
     * @throws BasketNotBoundException
     */
    public function deleteBasketBinding(string $basketId, bool $orderCompleted = false): void;

    /**
     * @throws BasketExpiredException
     */
    public function getBasketBinding(string $basketId, ?string $browserId = null): BasketBindingResponse;

    public function initializeBasketBinding(string $basketId): BasketBindingKeyResponse;

    /**
     * @throws BasketNotFoundException
     * @throws BasketExpiredException
     */
    public function updateBasket(string $basketId, Basket $basket): UpdateBasketResponse;
}
