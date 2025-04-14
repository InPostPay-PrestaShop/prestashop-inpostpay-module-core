<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command;

use izi\prestashop\MerchantApi\Handler\CreateOrderHandler;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;

/**
 * @see CreateOrderHandler
 */
final class CreateOrderCommand
{
    /**
     * @var CreateOrderRequest
     */
    private $request;

    public function __construct(CreateOrderRequest $request)
    {
        $this->request = $request;
    }

    public function getRequest(): CreateOrderRequest
    {
        return $this->request;
    }
}
