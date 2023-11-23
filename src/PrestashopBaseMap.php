<?php

namespace izi\prestashop;

class PrestashopBaseMap
{
    public function readDeliveryType()
    {
        return 'APM';
    }

    public function mapDeliveryOptions()
    {
        $option = new \izi\item\DeliveryOption();

        $option->delivery_name = 'Paczka w Weekend';
        $option->delivery_code_value = 'PWW';
        $option->delivery_option_price = $this->mapDeliveryOptionPrice();

        return [];
    }

    public function readComments($wooCommerce)
    {
        if ($customer = $wooCommerce->customer) {
            if ($order = $customer->get_last_order()) {
                return $order->get_customer_note();
            }
        }

        return '';
    }
}
