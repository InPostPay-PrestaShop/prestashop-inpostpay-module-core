<?php

namespace izi\prestashop\rest\order;

use izi\prestashop\CartSession;
use izi\prestashop\Logger;
use izi\prestashop\rest\SignatureVerification;

class Create
{
    public function handleRequest($data): ?int
    {
        $signature = new SignatureVerification();
        $signature->check();

        $basketId = $data->order_details->basket_id;
        $cartId = CartSession::getSessionId($basketId);

        $customerId = $this->createCustomer($data->account_info);
        $cart = new \Cart($cartId);
        $cart->id_customer = $customerId;
        $cart->save();
        $cart = new \Cart($cartId);

        $cart->id_address_delivery = $this->createDeliveryAddress($data->account_info, $customerId);
        if (isset($data->invoice_details)) {
            $cart->id_address_invoice = $this->createInvoiceAddress($data->invoice_details, $data->account_info);
        } else {
            $cart->id_address_invoice = $this->createDeliveryAddress($data->account_info, $customerId);
        }
        $cart->id_lang = (int)\Configuration::get('PS_LANG_DEFAULT');
        $cart->id_currency = \Context::getContext()->currency->id;
        $cart->setDeliveryOption([$cart->id_address_delivery => (int)\Configuration::get('INPOST_PAY_payment_' . strtolower($data->delivery->delivery_type))]);
        Logger::log("SELECTED CARRIER IS {$cart->id_carrier}");
        $cart->save();

        if (!empty($data->order_details->order_comments)){
            $old_message = \Message::getMessageByCartId((int) $cartId);
            if ($old_message){
                $update_message = new \Message((int) $old_message['id_message']);
                $update_message->message = \pSQL($data->order_details->order_comments);
                $update_message->update();
            }else{
                $update_message = new \Message();
                $update_message->message = \pSQL($data->order_details->order_comments);
                $update_message->id_cart = $cartId;
                $update_message->add();
            }


        }

        $paymentModuleName = 'inpostizi';
        $payment_module = \Module::getInstanceByName($paymentModuleName);
        @$payment_module->validateOrder($cart->id, \Configuration::get('PS_OS_BANKWIRE'), $cart->getOrderTotal(), 'Inpost Pay', 'Inpost Pay');

        $orderMessage = new \Message();
        $orderMessage->id_order = $payment_module->currentOrder;
        $orderMessage->message = 'Zamówienie wykonane przez Inpost Pay';
        $orderMessage->private = true;
        $orderMessage->save();

        $order = new \Order($payment_module->currentOrder);
        $order->id_carrier = (int)\Configuration::get('INPOST_PAY_payment_' . strtolower($data->delivery->delivery_type));

        $free = false;
        foreach ($cart->getCartRules() as $rule) {
            if ($rule['free_shipping']) {
                $free = true;
                break;
            }
        }
        if (!$free) {
            $order->refreshShippingCost();
        }


        $orderCarrierId = (int) $order->getIdOrderCarrier();
        if ($orderCarrierId > 0) {

            $additionalDeliveryOprionsPrice = 0.0;
            if (isset($data->delivery->delivery_codes) && is_array($data->delivery->delivery_codes)) {
                foreach ($data->delivery->delivery_codes as $additionalDeliveryOprion) {
                    $additionalDeliveryOprionsPrice += floatval(str_replace(',', '.', \Configuration::get('INPOST_PAY_payment_courier_' . strtolower($additionalDeliveryOprion))));
                }
            }
            $additionalDeliveryOprionsPriceGross = $additionalDeliveryOprionsPrice * 1.23;

            $order->total_shipping_tax_excl += $additionalDeliveryOprionsPrice;
            $order->total_shipping_tax_incl += $additionalDeliveryOprionsPriceGross;
            $order->total_shipping = $order->total_shipping_tax_incl;
            $order->total_paid_tax_excl += $additionalDeliveryOprionsPrice;
            $order->total_paid_tax_incl += $additionalDeliveryOprionsPriceGross;
            $order->total_paid = $order->total_paid_tax_incl;
            $order->update();

            $order_carrier = new \OrderCarrier($orderCarrierId);
            $order_carrier->shipping_cost_tax_excl = $additionalDeliveryOprionsPrice;
            $order_carrier->shipping_cost_tax_incl = $additionalDeliveryOprionsPriceGross;
            $order_carrier->update();
        }

        if (\Context::getContext()->customer->isLogged()) {
            $link = \Context::getContext()->link->getPageLink('history') . "?controller=history&inpost-thank-you-insert=true";
        } else {
            $link = \Context::getContext()->link->getPageLink('guest-tracking') . "?controller=guest-tracking&inpost-thank-you-insert=true";
        }

        CartSession::setCartOrderRedirectUrl($basketId, $link);
        CartSession::setOrderData($basketId, $order->id, json_encode($data));

        if (class_exists('\InPostCartChoiceModel')) {
            try {
                $model = new \InPostCartChoiceModel();
                $model->id = $cartId;
                $model->service = $data->delivery->delivery_type == 'APM' ? 'inpost_locker_standard' : 'inpost_courier_standard';
                if ($data->delivery->delivery_type == 'APM') {
                    $model->point = $data->delivery->delivery_point;
                }
                $model->email = $data->delivery->mail;
                $model->phone = $data->delivery->phone_number->phone;
                $model->add();
            } catch (\Exception $e) {
                \izi\prestashop\Logger::log($e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            }
        }

        return $payment_module->currentOrder;
    }

    public function getCountryId($code)
    {
        $isoCode = strtoupper($code);

        foreach (\Country::getCountries(\Context::getContext()->cart->id_lang) as $country) {
            if ($country['iso_code'] === $isoCode) {
                return $country['id_country'];
            }
        }
    }

    public function createDeliveryAddress($delivery, $idCustomer = null)
    {
        $address = new \Address();
        $address->alias = $delivery->client_address->address;
        $address->firstname = $delivery->name;
        $address->lastname = $delivery->surname;
        $address->city = $delivery->client_address->city;
        $address->id_state = 0;
        $address->id_customer = $idCustomer;
        $address->id_country = $this->getCountryId($delivery->client_address->country_code);
        $address->address1 = $delivery->client_address->address;
        $address->postcode = $delivery->client_address->postal_code;
        $address->phone = $delivery->phone_number->country_prefix . ' ' . $delivery->phone_number->phone;

        $address->add();

        return $address->id;
    }

    public function createInvoiceAddress($invoiceDetails, $accountInfo)
    {
        $address1 = $invoiceDetails->street . " " . $invoiceDetails->building . " " . ($invoiceDetails->flat ?? '');

        $address = new \Address();
        $address->alias = $address1;
        $address->firstname = ($invoiceDetails->name ?? $accountInfo->name);
        $address->lastname = ($invoiceDetails->surname ?? $accountInfo->surname);
        $address->city = $invoiceDetails->city;
        $address->id_state = 0;
        $address->id_customer = null;
        $address->id_country = $this->getCountryId($invoiceDetails->country_code);
        $address->address1 = $address1;
        $address->postcode = $invoiceDetails->postal_code;

        $address->company = ($invoiceDetails->company_name ?? '');

        $address->add();

        return $address->id;
    }

    public function createAddress($accountInfo, $idCustomer = null)
    {
        $address = new \Address();
        $address->alias = $accountInfo->client_address->address;
        $address->firstname = $accountInfo->name;
        $address->lastname = $accountInfo->surname;
        $address->city = $accountInfo->client_address->city;
        $address->id_state = 0;
        $address->id_customer = $idCustomer;
        $address->id_country = $this->getCountryId($accountInfo->client_address->country_code);
        $address->address1 = $accountInfo->client_address->address;
        $address->postcode = $accountInfo->client_address->postal_code;

        $address->add();

        return $address->id;
    }

    public function createCustomer($accountInfo)
    {
        $customer = new \Customer();
        $customer->getByEmail($accountInfo->mail);
        if (!$customer->id) {
            $customer->email = $accountInfo->mail;
            $customer->lastname = $accountInfo->name;
            $customer->firstname = $accountInfo->surname;
            $customer->passwd = 'no password';
            $customer->add();
        }
        return $customer->id;
    }
}
