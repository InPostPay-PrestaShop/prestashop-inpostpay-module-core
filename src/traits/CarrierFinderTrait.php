<?php

namespace izi\prestashop\traits;

trait CarrierFinderTrait
{
    /**
     * @param "apm"|"courier" $delivery_type
     *
     * @return int|null
     */
    private function getCarrierId($delivery_type)
    {
        $referenceId = (int) \Configuration::get('INPOST_PAY_payment_' . strtolower($delivery_type));
        $carrier = \Carrier::getCarrierByReference($referenceId);

        if (false === $carrier) {
            return null;
        }

        return $carrier->id;
    }
}
