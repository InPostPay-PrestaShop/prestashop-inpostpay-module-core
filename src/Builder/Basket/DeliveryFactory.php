<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Builder\PriceFactory;
use izi\prestashop\Common\Basket\DeliveryOption;
use izi\prestashop\Common\Basket\OptionalService;
use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;

class DeliveryFactory
{
    /**
     * @return DeliveryOption[]
     */
    public function getAvailableDeliveryOptions(\Cart $cart): array
    {
        $options = [];

        $free = null;

        foreach (DeliveryType::cases() as $deliveryType) {
            if (null === $carrier = $this->getCarrier($deliveryType)) {
                continue;
            }

            $carrierId = (int) $carrier->id;

            if (!$this->isDeliveryOptionAvailable($cart, $carrierId)) {
                continue;
            }

            if (!isset($free)) {
                $free = $this->hasFreeShippingCartRule($cart);
            }

            if (!$free) {
                $gross = $cart->getPackageShippingCost($carrierId);
                $net = $cart->getPackageShippingCost($carrierId, false);
            } else {
                $gross = $net = 0.0;
            }

            // TODO make configurable?
            $deliveryDate = (new \DateTimeImmutable())->setTimestamp(strtotime('+2 days'))->setTime(12, 0);

            $options[] = new DeliveryOption(
                $deliveryType,
                $deliveryDate,
                PriceFactory::create($net, $gross),
                $this->getOptionalServices($deliveryType, $cart, $carrier)
            );
        }

        return $options;
    }

    /**
     * @return OptionalService[]
     */
    private function getOptionalServices(DeliveryType $deliveryType, \Cart $cart, \Carrier $carrier): array
    {
        $services = [];

        foreach (ServiceCode::cases() as $code) {
            if (!$this->checkServiceAvailability($code, $deliveryType)) {
                continue;
            }

            if (0. >= $netPrice = (float) \Configuration::get(sprintf('INPOST_PAY_payment_%s_%s', strtolower($deliveryType->value), strtolower($code->value)))) {
                continue;
            }

            $address = $this->getTaxAddress($cart);
            $grossPrice = $carrier->getTaxCalculator($address)->addTaxes($netPrice);

            $services[] = new OptionalService(
                $this->getOptionalServiceName($code),
                $code,
                PriceFactory::create($netPrice, $grossPrice)
            );
        }

        return $services;
    }

    private function getTaxAddress(\Cart $cart): \Address
    {
        if ($type = \Configuration::get('PS_TAX_ADDRESS_TYPE')) {
            $addressId = $cart->$type;
        } else {
            $addressId = $cart->id_address_delivery;
        }

        return new \Address($addressId);
    }

    // TODO translate
    private function getOptionalServiceName(ServiceCode $code): string
    {
        switch ($code) {
            case ServiceCode::Cod():
                return 'Pobranie';
            case ServiceCode::Pww():
                return 'Paczka w Weekend';
            default:
                return $code->value;
        }
    }

    private function checkServiceAvailability(ServiceCode $option, DeliveryType $deliveryType): bool
    {
        $dayOfWeek = date('N');
        $hour = date('H');

        $deliveryType = strtolower($deliveryType->value);
        $option = strtolower($option->value);

//      TODO: implement new config?
        $dayFrom = \Configuration::get('INPOST_PAY_payment_' . $deliveryType . '_' . $option . '_from_day');
        $dayTo = \Configuration::get('INPOST_PAY_payment_' . $deliveryType . '_' . $option . '_to_day');
        $hourFrom = \Configuration::get('INPOST_PAY_payment_' . $deliveryType . '_' . $option . '_from_time');
        $hourTo = \Configuration::get('INPOST_PAY_payment_' . $deliveryType . '_' . $option . '_to_time');

        if ($dayOfWeek < $dayFrom || $dayOfWeek > $dayTo) {
            return false;
        }

        if ($dayOfWeek === $dayFrom && $hour < $hourFrom) {
            return false;
        }

        if ($dayOfWeek === $dayTo && $hour > $hourTo) {
            return false;
        }

        return true;
    }

    private function isDeliveryOptionAvailable(\Cart $cart, int $carrierId): bool
    {
        $deliveryOptionList = $cart->getDeliveryOptionList();
        $addressId = (int) $cart->id_address_delivery;

        if (!isset($deliveryOptionList[$addressId])) {
            return false;
        }

        foreach ($deliveryOptionList[$addressId] as $option) {
            if (isset($option['carrier_list'][$carrierId]) && 1 === count($option['carrier_list'])) {
                return true;
            }
        }

        return false;
    }

    private function hasFreeShippingCartRule(\Cart $cart): bool
    {
        return [] !== $cart->getCartRules(\CartRule::FILTER_ACTION_SHIPPING, false);
    }

    private function getCarrier(DeliveryType $deliveryType): ?\Carrier
    {
        if (0 >= $referenceId = (int) \Configuration::get('INPOST_PAY_payment_' . strtolower($deliveryType->value))) {
            return null;
        }

        $carrier = \Carrier::getCarrierByReference($referenceId);

        if (false === $carrier) {
            return null;
        }

        return $carrier;
    }
}
