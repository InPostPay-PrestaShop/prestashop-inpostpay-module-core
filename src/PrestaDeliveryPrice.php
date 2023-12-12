<?php

namespace izi\prestashop;

use izi\prestashop\traits\CarrierFinderTrait;
use izi\prestashop\traits\PriceFactoryTrait;

class PrestaDeliveryPrice
{
    use PriceFactoryTrait;
    use CarrierFinderTrait;

    public function mapDelivery(\Cart $cart)
    {
        $options = [];

        $free = null;

        foreach (['apm', 'courier'] as $deliveryType) {
            if (null === $carrierId = $this->getCarrierId($deliveryType)) {
                continue;
            }

            try {
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
            } catch (\Exception $e) {
                Logger::log("EXCEPTION IN DELIVERY PRICE {$e->getMessage()}");
                continue;
            }

            $delivery = new \izi\item\Delivery();
            $delivery->delivery_type = strtoupper($deliveryType);
            $delivery->delivery_date = date("Y-m-d\T12:00:00.000\Z", strtotime(' + 2 day'));
            $delivery->delivery_options = $this->mapDeliveryOptions($deliveryType);
            $delivery->delivery_price = $this->createPrice($net, $gross);
            $options[] = $delivery;
        }

        return $options;
    }

    public function mapDeliveryOptionPrice($net)
    {
        return $this->createPrice($net, $net * 1.23);
    }

    public function mapDeliveryOptions($deliveryType)
    {
        $data = [];
        $pwwPrice = (float) \Configuration::get('INPOST_PAY_payment_' . $deliveryType . '_pww');
        $pwwPriceAvailable = $this->optionAvailability('pww', $deliveryType);
        $codPrice = (float) \Configuration::get('INPOST_PAY_payment_' . $deliveryType . '_cod');
        $codPriceAvailable = $this->optionAvailability('cod', $deliveryType);

        Logger::log("CENA PWW {$pwwPrice}, DOSTEPNOPSC PWW {$pwwPriceAvailable}");
        Logger::log("CENA COD {$codPrice}, DOSTEPNOPSC COD {$codPriceAvailable}");

        if ($pwwPrice && $pwwPriceAvailable) {
            $option = new \izi\item\DeliveryOption();
            $option->delivery_name = 'Paczka w Weekend';
            $option->delivery_code_value = 'PWW';
            $option->delivery_option_price = $this->mapDeliveryOptionPrice($pwwPrice);
            $data[] = $option;
        }

        if ($codPrice && $codPriceAvailable) {
            $option = new \izi\item\DeliveryOption();
            $option->delivery_name = 'Pobranie';
            $option->delivery_code_value = 'COD';
            $option->delivery_option_price = $this->mapDeliveryOptionPrice($codPrice);
            $data[] = $option;
        }

        return $data;
    }

    /**
     * @param string $option
     * @param string $deliveryType
     *
     * @return bool
     */
    private function optionAvailability($option, $deliveryType)
    {
        $dayOfWeek = date('N');
        $hour = date('H');

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
}
