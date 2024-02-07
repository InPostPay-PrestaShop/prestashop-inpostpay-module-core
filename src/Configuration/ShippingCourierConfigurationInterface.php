<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Configuration\DTO\Shipping;

interface ShippingCourierConfigurationInterface
{
    public function getCourierShipping(?int $idShop): Shipping;

    public function setCourierShipping(Shipping $shipping): self;
}
