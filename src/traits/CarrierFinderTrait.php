<?php

namespace izi\prestashop\traits;

trait CarrierFinderTrait
{
    /**
     * @param "apm"|"courier" $delivery_type
     */
    private function getCarrierId(string $delivery_type): ?int
    {
        if (0 >= $referenceId = (int) \Configuration::get('INPOST_PAY_payment_' . strtolower($delivery_type))) {
            return null;
        }

        $carrier = \Carrier::getCarrierByReference($referenceId);

        if (false === $carrier) {
            return null;
        }

        return (int) $carrier->id;
    }
}
