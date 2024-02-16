<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Configuration\DTO\Shipping;

interface ShippingConfigurationInterface
{
    public function getApmShippingOptions(int $shopId = null): Shipping;

    public function getCourierShippingOptions(int $shopId = null): Shipping;
}
