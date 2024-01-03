<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket;

use izi\prestashop\BasketApp\Basket\Request\Basket;
use izi\prestashop\BasketApp\Basket\Request\BindingMethod;
use izi\prestashop\BasketApp\Basket\Request\BindingRequest;
use izi\prestashop\BasketApp\Basket\Response\BasketBindingResponse;
use izi\prestashop\BasketApp\Basket\Response\QrCode;
use izi\prestashop\BasketApp\Basket\Response\UpsertBasketResponse;
use izi\prestashop\BasketApp\Exception\BasketAlreadyBoundException;
use izi\prestashop\BasketApp\Exception\BasketExpiredException;
use izi\prestashop\BasketApp\Exception\BasketNotBoundException;
use izi\prestashop\BasketApp\Exception\BasketNotFoundException;
use izi\prestashop\BasketApp\Exception\BrowserNotFoundException;
use izi\prestashop\BasketApp\Exception\PhoneBindingUnavailableException;

interface BasketsApiClientInterface
{
    /**
     * @throws BasketNotFoundException
     * @throws BrowserNotFoundException
     * @throws BasketExpiredException
     */
    public function upsertBasket(string $basketId, Basket $basket): UpsertBasketResponse;

    /**
     * @throws BasketNotFoundException
     * @throws BasketExpiredException
     * @throws BasketNotBoundException
     */
    public function deleteBasketBinding(string $basketId, bool $orderCompleted = false): void;

    /**
     * @param BindingRequest<BindingMethod::Phone> $bindingRequest
     *
     * @throws BasketAlreadyBoundException
     * @throws BasketExpiredException
     * @throws PhoneBindingUnavailableException
     */
    public function bindBasketsByPhoneNumber(string $basketId, BindingRequest $bindingRequest): void;

    /**
     * @param BindingRequest<BindingMethod::DeepLink> $bindingRequest
     *
     * @throws BasketAlreadyBoundException
     * @throws BasketExpiredException
     */
    public function bindBasketsByDeepLink(string $basketId, BindingRequest $bindingRequest): QrCode;

    /**
     * @throws BasketExpiredException
     */
    public function getBasketBinding(string $basketId, string $browserId = null): BasketBindingResponse;
}
