<?php

namespace izi\prestashop;

use izi\item\order\OrderProduct;
use izi\prestashop\BasketApp\BasketAppClientInterface;
use izi\prestashop\Common\Basket\ConsentRequirementType;

class PrestashopOrder
{
    private $basketId;
    private $order;
    private $customer;
    private $deliveryDetails;

    private $orderData;

    public function __construct(\Order $order, string $basketId)
    {
        $this->order = $order;
        $this->basketId = $basketId;

        $this->deliveryDetails = new \Address((int) $this->order->id_address_delivery);
        $this->customer = new \Customer((int) $this->order->id_customer);
    }

    public static function getOrder(\Order $order, string $basketId): \izi\item\order\Order
    {
        return (new self($order, $basketId))->mapOrder();
    }

    public function mapOrder(): \izi\item\order\Order
    {
        $order = new \izi\item\order\Order();

        $order->account_info = $this->mapAccountInfo();
        $order->invoice_details = $this->mapInvoiceDetails();
        $order->delivery = $this->mapDelivery();
        $order->products = $this->mapProducts();
        $order->order_details = $this->mapOrderDetails();
        $order->consents = $this->mapConsents();

        return $order;
    }

    public function mapConsents()
    {
        if ($data = $this->getOrderData()) {
            return $data->consents;
        }

        $config = json_decode(\Configuration::get('INPOST_PAY_CONSENTS'), true) ?? [];

        if ([] === $config) {
            return [];
        }

        $consents = [];

        foreach ($config as $consent) {
            $date = \DateTimeImmutable::createFromFormat(\DateTime::RFC3339, $consent['dateUpdated']);

            $consents[] = [
                'consent_id' => $consent['id'],
                'consent_version' => false === $date ? '0' : (string) $date->getTimestamp(),
                'is_accepted' => $consent['requirementType'] !== ConsentRequirementType::Optional()->value,
            ];
        }

        return $consents;
    }

    public function mapAccountInfo()
    {
        $data = $this->getOrderData();

        if ($data && isset($data->account_info)) {
            return $data->account_info;
        }

        $accountInfo = new \izi\item\order\AccountInfo();

        $accountInfo->name = $this->customer->firstname;
        $accountInfo->surname = $this->customer->lastname;
        $accountInfo->phone_number = $this->mapPhoneNumber();
        $accountInfo->mail = $this->customer->email;
        $accountInfo->client_address = $this->mapClientAddress();

        return $accountInfo;
    }

    public function mapProducts()
    {
        $basket = CartSession::getBasketCacheById($this->basketId);
        if (!$basket) {
            return [];
        }

        $basket = json_decode($basket, false);

        return array_map(static function ($product) {
            return OrderProduct::fromBasketProduct($product);
        }, $basket->products);
    }

    public function mapClientAddress()
    {
        $clientAddress = new \izi\item\order\ClientAddress();

        $clientAddress->country_code = \Country::getIsoById($this->deliveryDetails->id_country);
        $clientAddress->address = $this->deliveryDetails->address1 . ' ' . $this->deliveryDetails->address2;
        $clientAddress->city = $this->deliveryDetails->city;
        $clientAddress->postal_code = $this->deliveryDetails->postcode;

        return $clientAddress;
    }

    public function mapInvoiceDetails()
    {
        $data = $this->getOrderData();

        if ($data && isset($data->invoice_details)) {
            return $data->invoice_details;
        }

        return null;
    }

    public function mapDelivery()
    {
        $data = $this->getOrderData();
        $delivery = new \izi\item\order\Delivery();

        $deliveryCodes = $data && isset($data->delivery->delivery_codes) ? $data->delivery->delivery_codes : [];

        $serviceNameDictionary = [
            'PWW' => 'Paczka w Weekend',
            'COD' => 'Pobranie',
        ];

        $delivery->delivery_options = array_map(function ($code) use ($serviceNameDictionary) {
            return [
                'delivery_name' => $serviceNameDictionary[$code] ?? $code,
                'delivery_code_value' => $code,
                'delivery_option_price' => $this->createPrice(0., 0.),
            ];
        }, $deliveryCodes);

        $delivery->delivery_type = $data && isset($data->delivery->delivery_type) ? $data->delivery->delivery_type : 'COURIER';
        $delivery->delivery_price = $this->createPrice(
            $this->order->total_shipping_tax_excl,
            $this->order->total_shipping_tax_incl
        );
        $delivery->delivery_date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->order->date_add)
            ->setTimestamp(strtotime('+2 days'))
            ->setTime(12, 0)
            ->setTimezone(new \DateTimeZone(BasketAppClientInterface::DATETIME_ZONE))
            ->format(BasketAppClientInterface::DATETIME_FORMAT);

        $delivery->mail = $this->order->getCustomer()->email;
        $delivery->phone_number = $this->mapPhone();
        $delivery->delivery_address = $this->mapDeliveryAddress();

        $delivery->courier_note = $this->readComments();

        $deliveryPoint = isset($data->delivery, $data->delivery->delivery_point) ? $data->delivery->delivery_point : ''; // get_post_meta($this->orderId, 'delivery_point', true);
        if ($deliveryPoint) {
            $delivery->delivery_point = $deliveryPoint;
        }

        return $delivery;
    }

    public function mapDeliveryAddress()
    {
        if ($data = $this->getOrderData()) {
            if (isset($data->delivery, $data->delivery->delivery_address)) {
                return $data->delivery->delivery_address;
            }

            if (isset($data->account_info)) {
                $deliveryAddress = new \izi\item\order\DeliveryAddress();
                $deliveryAddress->name = $data->account_info->name . ' ' . $data->account_info->surname;
                $deliveryAddress->country_code = $data->account_info->client_address->country_code;
                $deliveryAddress->address = $data->account_info->client_address->address;
                $deliveryAddress->city = $data->account_info->client_address->city;
                $deliveryAddress->postal_code = $data->account_info->client_address->postal_code;

                return $deliveryAddress;
            }
        }

        $deliveryAddress = new \izi\item\order\DeliveryAddress();

        $deliveryAddress->name = $this->customer->firstname . ' ' . $this->customer->lastname;
        $deliveryAddress->country_code = 'PL';
        $deliveryAddress->address = $this->deliveryDetails->address1 . ' ' . $this->deliveryDetails->address2;
        $deliveryAddress->city = $this->deliveryDetails->city;
        $deliveryAddress->postal_code = $this->deliveryDetails->postcode;

        return $deliveryAddress;
    }

    public function mapPhone()
    {
        $phone = new \izi\item\order\Phone();

        $trigPhone = $this->readPhone();
        $phone->country_prefix = $trigPhone[0];
        $phone->phone = $trigPhone[1];

        return $phone;
    }

    public function mapPhoneNumber()
    {
        $phoneNumber = new \izi\item\order\PhoneNumber();

        $trigPhone = $this->readPhone();
        $phoneNumber->country_prefix = $trigPhone[0];
        $phoneNumber->phone = $trigPhone[1];

        return $phoneNumber;
    }

    public function readPhone()
    {
        foreach (['phone', 'phone_mobile'] as $field) {
            $value = (string) $this->deliveryDetails->{$field};

            if ('' !== $value && preg_match('/^\+\d+ /', $value)) {
                return explode(' ', $value, 2);
            }
        }

        return ['+48', $this->deliveryDetails->phone];
    }

    private function readComments()
    {
        return $this->order->getFirstMessage() ?: null;
    }

    public function mapOrderDetails()
    {
        $orderDetails = new \izi\item\order\OrderDetails();

        $orderDetails->order_comments = $this->readComments();
        $orderDetails->order_id = $this->order->id;
        $orderDetails->pos_id = $this->getConfiguration('INPOST_PAY_pos_id');
        $orderDetails->order_creation_date = date("Y-m-d\TH:i:s.000\Z", strtotime($this->order->date_add));
        $orderDetails->basket_id = $this->basketId;

        $orderDetails->order_merchant_status_description = $this->getStatusDescription($this->order);
        $orderDetails->order_base_price = $this->readSummaryOrderBasePrice();
        $orderDetails->order_final_price = $this->readSummaryOrderFinalPrice();
        $orderDetails->delivery_references_list = [];
        $orderDetails->currency = 'PLN';
        $orderDetails->payment_type = $this->readPaymentType();

        return $orderDetails;
    }

    public function readSummaryOrderFinalPrice()
    {
        return $this->createPrice($this->order->total_paid_tax_excl, $this->order->total_paid_tax_incl);
    }

    public function readSummaryOrderBasePrice()
    {
        $gross = $this->order->total_paid_tax_incl - $this->order->total_shipping_tax_incl;
        $net = $this->order->total_paid_tax_excl - $this->order->total_shipping_tax_excl;

        return $this->createPrice($net, $gross);
    }

    public function readPaymentType()
    {
        return 'BLIK_CODE';
    }

    /**
     * @return false|string
     */
    private function getConfiguration(string $key, ?int $languageId = null)
    {
        return \Configuration::get($key, $languageId, null, $this->order->id_shop);
    }

    /**
     * @return \StdClass|null
     */
    private function getOrderData()
    {
        if (isset($this->orderData)) {
            return $this->orderData;
        }

        if (!$data = CartSession::getOrderData($this->order->id)) {
            $this->orderData = false;
        } else {
            $this->orderData = json_decode($data, false);
        }

        return $this->orderData ?: null;
    }

    private function getStatusDescription(\Order $order): string
    {
        $orderStateId = (int) $order->current_state;
        $config = \Configuration::get('INPOST_PAY_OS_DESCRIPTION_MAP', $order->id_lang, null, $order->id_shop);
        $map = $config ? json_decode($config, true) : [];

        return $map[$orderStateId] ?? (new \OrderState($orderStateId, $order->id_lang))->name;
    }

    private function createPrice(float $net, float $gross): \izi\item\Price
    {
        $net = \Tools::ps_round($net, 2);
        $gross = \Tools::ps_round($gross, 2);
        $vat = $gross - $net;

        $price = new \izi\item\Price();

        $price->net = $this->formatPrice($net);
        $price->gross = $this->formatPrice($gross);
        $price->vat = $this->formatPrice($vat);

        return $price;
    }

    private function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }
}
