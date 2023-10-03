<?php

namespace izi\prestashop;

class PrestaDeliveryPrice
{
    public function mapDelivery($cart)
    {
        $options = [];

        foreach (['apm', 'courier'] as $deliveryType) {
            $type = \Configuration::get('INPOST_PAY_payment_' . $deliveryType);
            $gross = 0.0;
            $net = 0.0;
            $free = false;
            try {
                foreach ($cart->getCartRules() as $rule) {
                    if ($rule['free_shipping']) {
                        $free = true;
                        break;
                    }
                }
                if (!$free) {
                    $gross = $cart->getPackageShippingCost($type, true);
                    $net = $cart->getPackageShippingCost($type, false);
                }
            } catch (\Exception $e) {
                Logger::log("EXCEPTION IN DELIVERY PRICE {$e->getMessage()}");
                continue;
            }

            $delivery = new \izi\item\Delivery();
            $delivery->delivery_type = strtoupper($deliveryType);
            $delivery->delivery_date = date("Y-m-d\T12:00:00.000\Z", strtotime(" + 2 day"));
            $delivery->delivery_options = $this->mapDeliveryOptions($deliveryType);
            $delivery->delivery_price = $this->mapDeliveryPrice($net, $gross);
            $options[] = $delivery;
        }
        return $options;
    }

    public function mapDeliveryPrice($net, $gross)
    {
        $price = new \izi\item\Price();
        $vat = $gross - $net;
        $price->gross = number_format($gross, 2, '.', '');
        $price->net = number_format($net, 2, '.', '');
        $price->vat = number_format($vat, 2, '.', '');

        return $price;
    }

    public function mapDeliveryOptionPrice($net)
    {
        $price = new \izi\item\Price();
        $gross = $net * 1.23;
        $vat = $gross - $net;
        $price->gross = number_format($gross, 2, '.', '');
        $price->net = number_format($net, 2, '.', '');
        $price->vat = number_format($vat, 2, '.', '');

        return $price;
    }

    public function mapDeliveryOptions($deliveryType)
    {
        $data = [];
        $pwwPrice = floatval(\Configuration::get('INPOST_PAY_payment_' . $deliveryType . '_pww'));
        $pwwPriceAvailable = $this->optionAvailability('pww', $deliveryType);
        $codPrice = floatval(\Configuration::get('INPOST_PAY_payment_' . $deliveryType . '_cod'));
        $codPriceAvailable = $this->optionAvailability('cod', $deliveryType);;

        Logger::log("CENA PWW {$pwwPrice}, DOSTEPNOPSC PWW {$pwwPriceAvailable}");
        Logger::log("CENA COD {$codPrice}, DOSTEPNOPSC COD {$codPriceAvailable}");

        if ($pwwPrice && $pwwPriceAvailable) {
            $option = new \izi\item\DeliveryOption();
            $option->delivery_name = "Paczka w Weekend";
            $option->delivery_code_value = "PWW";
            $option->delivery_option_price = $this->mapDeliveryOptionPrice($pwwPrice);
            $data[] = $option;
        }

        if ($codPrice && $codPriceAvailable) {
            $option = new \izi\item\DeliveryOption();
            $option->delivery_name = "Pobranie";
            $option->delivery_code_value = "COD";
            $option->delivery_option_price = $this->mapDeliveryOptionPrice($codPrice);
            $data[] = $option;
        }

        return $data;
    }

    private function optionAvailability(string $option, string $deliveryType): bool
    {
        $dayOfWeek = date('N');
        $hour = date('H');

        $dayFrom = \Configuration::get('INPOST_PAY_payment_'. $deliveryType . '_' . $option . '_from_day');
        $dayTo = \Configuration::get('INPOST_PAY_payment_'. $deliveryType . '_' . $option . '_to_day');
        $hourFrom = \Configuration::get('INPOST_PAY_payment_'. $deliveryType . '_' . $option . '_from_time');
        $hourTo = \Configuration::get('INPOST_PAY_payment_'. $deliveryType . '_' . $option . '_to_time');

        if ($dayOfWeek < $dayFrom) {
            return false;
        }
        if ($dayOfWeek == $dayFrom) {
            if ($hour < $hourFrom) {
                return false;
            }
        }

        if ($dayOfWeek > $dayTo) {
            return false;
        }
        if ($dayOfWeek == $dayTo) {
            if ($hour > $hourTo) {
                return false;
            }
        }
        return true;
    }
}
