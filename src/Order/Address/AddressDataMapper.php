<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Address;

use izi\prestashop\Common\Order\DeliveryAddress;
use izi\prestashop\Common\PhoneNumber;

class AddressDataMapper
{
    public function mapDeliveryAddress(\Address $address): DeliveryAddress
    {
        return new DeliveryAddress(
            $this->getAddressLine($address),
            $address->city,
            $address->postcode,
            sprintf('%s %s', $address->firstname, $address->lastname),
            'PL'
        );
    }

    public function mapPhoneNumber(\Address $address): PhoneNumber
    {
        [$prefix, $phone] = $this->getPhoneNumberData($address);

        return new PhoneNumber($prefix, $phone);
    }

    private function getPhoneNumberData(\Address $address): array
    {
        foreach (['phone', 'phone_mobile'] as $field) {
            $value = (string) $address->{$field};

            if ('' !== $value && preg_match('/^\+\d+ /', $value)) {
                return explode(' ', $value, 2);
            }
        }

        $phone = $address->phone ?: $address->phone_mobile;

        return ['+48', (string) $phone];
    }

    private function getAddressLine(\Address $address): string
    {
        if (!$address->address2) {
            return (string) $address->address1;
        }

        return sprintf('%s %s', $address->address1, $address->address2);
    }
}
