<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;

interface ShippingConfigurationInterface
{
    public function getShippingOptions(DeliveryType $deliveryType, int $shopId = null): ShippingOptions;

    public function getApmShippingOptions(int $shopId = null): ShippingOptions;

    public function getCourierShippingOptions(int $shopId = null): ShippingOptions;
}
