<?php

declare(strict_types=1);

namespace izi\prestashop\Entities;

use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;

/**
 * @template T of BasketInterface
 */
interface BasketSessionInterface
{
    public function getBasketId(): string;

    public function isBasketBound(): bool;

    public function getBindingConfirmation(): ?BindingConfirmation;

    public function setBindingConfirmation(BindingConfirmation $confirmation);

    public function unbind();

    public function updatedBy(BasketEvent $event);

    public function wasUpdated(): bool;

    public function getOrderId(): ?string;

    public function getOrderConfirmationUrl(): ?string;

    public function finalize(string $orderId, string $orderConfirmationUrl, CreateOrderRequest $request);

    public function wasUserRedirected(): bool;

    public function redirect();

    /**
     * @return T
     */
    public function getBasket(): BasketInterface;
}
