<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command\Order;

use izi\prestashop\MerchantApi\Handler\Order\UpdateCartMessageHandler;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;

/**
 * @see UpdateCartMessageHandler
 */
final class UpdateCartMessageCommand
{
    /**
     * @var \Cart
     */
    private $cart;

    /**
     * @var CreateOrderRequest
     */
    private $request;

    public function __construct(\Cart $cart, CreateOrderRequest $request)
    {
        $this->cart = $cart;
        $this->request = $request;
    }

    public function getCart(): \Cart
    {
        return $this->cart;
    }

    public function getRequest(): CreateOrderRequest
    {
        return $this->request;
    }
}
