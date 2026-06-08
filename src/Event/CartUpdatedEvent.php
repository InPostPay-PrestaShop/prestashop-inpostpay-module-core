<?php

declare(strict_types=1);

namespace izi\prestashop\Event;

use Symfony\Component\HttpFoundation\Request;

final class CartUpdatedEvent extends Event
{
    /**
     * @var \Cart
     */
    private $cart;

    /**
     * @var Request|null
     */
    private $request;

    public function __construct(\Cart $cart, ?Request $request = null)
    {
        $this->cart = $cart;
        $this->request = $request;
    }

    public function getCart(): \Cart
    {
        return $this->cart;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }
}
