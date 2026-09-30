<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Event;

use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Event\Event;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;

final class OrderCreatedEvent extends Event
{
    /**
     * @var BasketSessionInterface
     */
    private $session;

    /**
     * @var CreateOrderRequest
     */
    private $request;

    /**
     * @var int
     */
    private $orderId;

    public function __construct(BasketSessionInterface $session, CreateOrderRequest $request, int $orderId)
    {
        $this->session = $session;
        $this->request = $request;
        $this->orderId = $orderId;
    }

    public function getSession(): BasketSessionInterface
    {
        return $this->session;
    }

    public function getRequest(): CreateOrderRequest
    {
        return $this->request;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }
}
