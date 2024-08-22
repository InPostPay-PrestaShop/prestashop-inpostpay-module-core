<?php

namespace izi\prestashop\Order\Address;

use izi\prestashop\Common\Order\DeliveryAddress;
use izi\prestashop\Common\PhoneNumber;
use izi\prestashop\ObjectModel\ObjectManager;

class AddressDataMapper
{
    public function mapDeliveryAddress(\Address $address): DeliveryAddress
    {
        return new DeliveryAddress(
            $address->firstname . ' ' . $address->lastname,
            'PL',
            $address->address1 . ' ' . $address->address2,
            $address->city,
            $address->postcode
        );
    }

    public function mapPhoneNumber(\Address $address)
    {
        $trigPhone = $this->readPhone($address);

        $phoneNumber = new PhoneNumber(
            $trigPhone[0],
            $trigPhone[1]
        );

        return $phoneNumber;
    }

    private function readPhone(\Address $address)
    {
        foreach (['phone', 'phone_mobile'] as $field) {
            $value = (string) $address->{$field};

            if ('' !== $value && preg_match('/^\+\d+ /', $value)) {
                return explode(' ', $value, 2);
            }
        }

        return ['+48', $address->phone];
    }
}
