<?php

declare(strict_types=1);

namespace izi\prestashop\Cart\Storage;

use izi\prestashop\Cart\Storage\Exception\StorageUnavailableException;
use Symfony\Component\HttpFoundation\Request;

/**
 * May be subject to race conditions if the customer cookie is updated across multiple requests
 * after products are added to the cart.
 */
final class CustomerCookieBasketIdStorage implements BasketIdStorageInterface
{
    /**
     * @var \Context
     */
    private $context;

    public function __construct(\Context $context)
    {
        $this->context = $context;
    }

    public function getBasketId(): ?string
    {
        $basketId = $this->getCookie()->inpostizi_basket_id;

        return false === $basketId ? null : $basketId;
    }

    public function setBasketId(?string $basketId): void
    {
        if (null === $basketId) {
            unset($this->getCookie()->inpostizi_basket_id);
        } else {
            $this->getCookie()->inpostizi_basket_id = $basketId;
        }
    }

    private function getCookie(): \Cookie
    {
        if (!isset($this->context->cookie)) {
            throw new StorageUnavailableException('Context cookie is not set.');
        }

        return $this->context->cookie;
    }
}
