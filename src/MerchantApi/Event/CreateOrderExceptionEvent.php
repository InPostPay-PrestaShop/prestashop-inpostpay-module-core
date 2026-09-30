<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Event;

use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Event\Event;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;

final class CreateOrderExceptionEvent extends Event
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
     * @var \Throwable
     */
    private $throwable;

    public function __construct(BasketSessionInterface $session, CreateOrderRequest $request, \Throwable $e)
    {
        $this->session = $session;
        $this->request = $request;
        $this->throwable = $e;
    }

    public function getSession(): BasketSessionInterface
    {
        return $this->session;
    }

    public function getRequest(): CreateOrderRequest
    {
        return $this->request;
    }

    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }
}
