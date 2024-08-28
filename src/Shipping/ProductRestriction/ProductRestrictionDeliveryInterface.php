<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\ProductRestriction;

interface ProductRestrictionDeliveryInterface
{
    /**
     * @return bool true if shipping is available based on the product carrier restrictions
     */
    public function isShippingAvailableBasedOnProductCarrierRestriction(\Carrier $carrier, \Product $product): bool;
}
