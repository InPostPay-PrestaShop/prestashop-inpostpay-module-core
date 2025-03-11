<?php

declare(strict_types=1);

namespace izi\prestashop\Repository;

use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;

interface OrderDataRepositoryInterface
{
    /**
     * @return CreateOrderRequest|null request data received by merchant API that resulted in order finalization
     */
    public function getOrderData(string $orderId): ?CreateOrderRequest;
}
