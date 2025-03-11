<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command;

use izi\prestashop\MerchantApi\Handler\GetOrderHandler;

/**
 * @see GetOrderHandler
 */
final class GetOrderCommand
{
    /**
     * @var string
     */
    private $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
