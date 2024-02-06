<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Configuration\DTO\Shipping;

interface ShippingAmpConfigurationInterface
{
    public function getAmpShipping(): Shipping;

    public function setAmpShipping(Shipping $shipping): self;
}
