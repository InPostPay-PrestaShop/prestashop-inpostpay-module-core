<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\ProductRestriction;

final class ProductRestrictionDelivery implements ProductRestrictionDeliveryInterface
{
    public function isShippingAvailableBasedOnProductCarrierRestriction(\Carrier $carrier, \Product $product): bool
    {
        $carriersRestricted = array_filter($product->getCarriers(), static function (array $carrier): bool {
            return (bool) $carrier['active'];
        });

        if ([] === $carriersRestricted) {
            return true;
        }

        $availableCarriers = array_map(static function (array $carrier) {
            return (int) $carrier['id_reference'];
        }, $carriersRestricted);

        return in_array((int) $carrier->id_reference, $availableCarriers, true);
    }
}
