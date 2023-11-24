<?php

namespace izi\prestashop\rest\order;

use izi\item\order\InvoiceDetails;
use izi\prestashop\CartSession;
use izi\prestashop\Exception\BasketNotFoundException;
use izi\prestashop\Exception\InternalServerErrorException;
use izi\prestashop\traits\CarrierFinderTrait;

class Create
{
    use CarrierFinderTrait;

    /**
     * @return int created order identifier
     */
    public function handleRequest($data)
    {
        $cart = $this->getCart($data->order_details->basket_id);

        if ($order = $this->getOrderByCart($cart)) {
            if ('inpostizi' !== $order->module) {
                throw new BasketNotFoundException('There already exists an order for this basket.');
            }

            return $order->id;
        }

        return $this->createOrder($cart, $data);
    }

    /**
     * @param string $basketId
     *
     * @return \Cart
     */
    private function getCart($basketId)
    {
        $cartId = CartSession::getSessionId($basketId);

        if (!$cartId || !\Validate::isLoadedObject($cart = new \Cart($cartId))) {
            throw new BasketNotFoundException('Basket not found.');
        }

        return $cart;
    }

    /**
     * @param \Cart $cart
     *
     * @return \Order|null
     */
    private function getOrderByCart(\Cart $cart)
    {
        if (is_callable([\Order::class, 'getByCartId'])) {
            return \Order::getByCartId($cart->id);
        }

        $orderId = \Order::getOrderByCartId($cart->id);

        return $orderId ? new \Order($orderId) : null;
    }

    private function createOrder(\Cart $cart, $data)
    {
        if (null === $carrierId = $this->getCarrierId($data->delivery->delivery_type)) {
            throw new InternalServerErrorException(sprintf('No valid carrier mapping configured for delivery type "%s"', $data->delivery->delivery_type));
        }

        $customer = $this->getOrCreateCustomer($cart, $data->account_info);
        $this->updateCart($cart, $data, $customer, $carrierId);

        $this->adjustHandlingCost($data);

        /** @var \Inpostizi $payment_module */
        $payment_module = \Module::getInstanceByName('inpostizi');
        $payment_module->validateOrder(
            $cart->id,
            \Configuration::get('PS_OS_BANKWIRE'), // TODO custom order status
            $cart->getOrderTotal(),
            'Inpost Pay',
            null,
            [],
            null,
            false,
            $cart->secure_key
        );

        $link = \Context::getContext()->link->getPageLink($customer->is_guest ? 'guest-tracking' : 'history', null, $cart->id_lang, [
            'inpost-thank-you-insert' => 'true',
        ]);

        CartSession::setCartOrderRedirectUrl($data->order_details->basket_id, $link);
        CartSession::setOrderData($data->order_details->basket_id, $payment_module->currentOrder, json_encode($data));

        $this->saveCarrierModuleData($cart->id, $data->delivery);

        return $payment_module->currentOrder;
    }

    private function updateCart(\Cart $cart, $data, \Customer $customer, $carrierId)
    {
        $cart->id_customer = $customer->id;
        $cart->secure_key = $customer->secure_key;

        $deliveryAddressId = $this->createDeliveryAddress($data->account_info, $customer);

        $cart->updateAddressId($cart->id_address_delivery, $deliveryAddressId);
        $cart->setDeliveryOption([$deliveryAddressId => $carrierId . ',']);

        if (isset($data->invoice_details)) {
            $cart->id_address_invoice = $this->createInvoiceAddress($data->invoice_details, $data->account_info, $customer);
        } else {
            $cart->id_address_invoice = $deliveryAddressId;
        }

        $cart->id_currency = \Currency::getIdByIsoCode('PLN');

        if (!$cart->update()) {
            throw new InternalServerErrorException('Could not update cart data.');
        }

        $this->updateCartMessage($cart->id, $data);
    }

    /**
     * @param string $code
     *
     * @return int|null
     */
    private function getCountryId($code)
    {
        return \Country::getByIso(strtoupper($code)) ?: null;
    }

    private function createDeliveryAddress($delivery, \Customer $customer)
    {
        $address = new \Address();
        $address->firstname = $delivery->name;
        $address->lastname = $delivery->surname;
        $address->city = $delivery->client_address->city;
        $address->id_customer = $customer->id;
        $address->id_country = $this->getCountryId($delivery->client_address->country_code);
        $address->address1 = $delivery->client_address->address;
        $address->postcode = $delivery->client_address->postal_code;
        $address->phone = $delivery->phone_number->country_prefix . ' ' . $delivery->phone_number->phone;

        // TODO find an existing address

        $address->alias = \Tools::substr($address->address1, 32);

        if (!$address->add()) {
            throw new InternalServerErrorException('Could not create delivery address.');
        }

        return $address->id;
    }

    private function createInvoiceAddress($invoiceDetails, $accountInfo, \Customer $customer)
    {
        $address = new \Address();
        $address->firstname = !empty($invoiceDetails->name) ? $invoiceDetails->name : $accountInfo->surname;
        $address->lastname = !empty($invoiceDetails->surname) ? $invoiceDetails->surname : $accountInfo->surname;
        $address->city = $invoiceDetails->city;
        $address->id_customer = $customer->id;
        $address->id_country = $this->getCountryId($invoiceDetails->country_code);
        $address->address1 = $invoiceDetails->street;
        $address->address2 = $invoiceDetails->building;
        if (!empty($invoiceDetails->flat)) {
            $address->address2 .= ' / ' . $invoiceDetails->flat;
        }
        $address->postcode = $invoiceDetails->postal_code;

        if (InvoiceDetails::LEGAL_FORM_COMPANY === $invoiceDetails->legal_form) {
            $address->company = $invoiceDetails->company_name;
            if (!empty($invoiceDetails->tax_id_prefix)) {
                $address->vat_number = sprintf('%s %s', $invoiceDetails->tax_id_prefix, $invoiceDetails->tax_id);
            } else {
                $address->vat_number = $invoiceDetails->tax_id;
            }
        }

        // TODO find an existing address

        $address->alias = \Tools::substr($address->address1 . ' ' . $address->address2, 32);

        if (!$address->add()) {
            throw new InternalServerErrorException('Could not create invoice address.');
        }

        return $address->id;
    }

    /**
     * @return \Customer
     */
    private function getOrCreateCustomer(\Cart $cart, $accountInfo)
    {
        $customer = new \Customer($cart->id_customer);

        if (!$customer->is_guest && \Validate::isLoadedObject($customer)) {
            return $customer;
        }

        $customer->email = $accountInfo->mail;
        $customer->lastname = $accountInfo->name;
        $customer->firstname = $accountInfo->surname;

        if (!\Validate::isLoadedObject($customer)) {
            $customer->id_lang = $cart->id_lang;
            $customer->passwd = 'no password'; // TODO hash random password
            $customer->is_guest = true;

            if (!$customer->add()) {
                throw new InternalServerErrorException('Could not create customer account.');
            }
        } elseif (!$customer->update()) {
            throw new InternalServerErrorException('Could not update customer account.');
        }

        return $customer;
    }

    private function adjustHandlingCost($data)
    {
        if (0. === $deliveryOptionsCost = $this->getAdditionalDeliveryOptionsCost($data->delivery)) {
            return;
        }

        $handlingCost = (float) \Configuration::get('PS_SHIPPING_HANDLING');
        \Configuration::set('PS_SHIPPING_HANDLING', $handlingCost + $deliveryOptionsCost);
        \Cache::clean('getPackageShippingCost_*');
        \Cart::resetStaticCache();
    }

    /**
     * @return float
     */
    private function getAdditionalDeliveryOptionsCost($deliveryData)
    {
        if (
            !isset($deliveryData->delivery_codes) ||
            !is_array($deliveryData->delivery_codes) ||
            [] === $deliveryData->delivery_codes
        ) {
            return 0.;
        }

        $additionalCosts = 0.;
        foreach ($deliveryData->delivery_codes as $optionCode) {
            $configKey = sprintf('INPOST_PAY_payment_%s_%s', strtolower($deliveryData->delivery_type), strtolower($optionCode));
            $additionalCosts += (float) str_replace(',', '.', \Configuration::get($configKey));
        }

        // TODO convert to default currency?

        return $additionalCosts;
    }

    /**
     * @param int $cartId
     */
    private function updateCartMessage($cartId, $data)
    {
        if (empty($data->order_details->order_comments)) {
            return;
        }

        $old_message = \Message::getMessageByCartId((int) $cartId);

        if ($old_message) {
            $message = new \Message((int) $old_message['id_message']);
        } else {
            $message = new \Message();
            $message->id_cart = $cartId;
        }

        $message->message = $data->order_details->order_comments;

        if (!$message->save()) {
            throw new InternalServerErrorException('Could not save order comments.');
        }
    }

    private function saveCarrierModuleData($cartId, $delivery)
    {
        if (!class_exists(\InPostCartChoiceModel::class)) {
            return;
        }

        try {
            $model = new \InPostCartChoiceModel();
            $model->id = $cartId;
            $model->service = 'APM' === $delivery->delivery_type ? 'inpost_locker_standard' : 'inpost_courier_standard';
            if ('APM' === $delivery->delivery_type) {
                $model->point = $delivery->delivery_point;
            }
            $model->email = $delivery->mail;
            $model->phone = $delivery->phone_number->phone;
            $model->add();
        } catch (\Exception $e) {
            \izi\prestashop\Logger::log($e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}
