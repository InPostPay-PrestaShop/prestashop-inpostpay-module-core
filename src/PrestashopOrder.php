<?php

namespace izi\prestashop;

use izi\item\order\OrderProduct;
use izi\prestashop\traits\PriceFactoryTrait;

class PrestashopOrder
{
    use PriceFactoryTrait;

    private $orderId;
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

        $consents = [];

        $selectedRequired = explode(',', $this->getConfiguration('INPOST_PAY_terms_options_required'));
        $selectedRequiredOnce = explode(',', $this->getConfiguration('INPOST_PAY_terms_options_required_once'));
        $selectedAdditional = explode(',', $this->getConfiguration('INPOST_PAY_terms_options_required_additional'));

        $cmsPages = \CMS::getCMSPages($this->order->id_lang, null, true, $this->order->id_shop);
        $consentId = 1;

        foreach ($cmsPages as $page) {
            $cmsId = $page['id_cms'];

            if (in_array($cmsId, $selectedRequired, false)) {
                $consents[] = [
                    'consent_id' => $consentId++,
                    'consent_version' => 1,
                    'is_accepted' => true,
                ];
            } elseif (in_array($cmsId, $selectedRequiredOnce, false)) {
                $consents[] = [
                    'consent_id' => $consentId++,
                    'consent_version' => 1,
                    'is_accepted' => true,
                ];
            } elseif (in_array($cmsId, $selectedAdditional, false)) {
                ++$consentId;
            }
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

    public function mapCartProduct($item)
    {
        $product = $this->mapProductData($item);

        $product->quantity = $this->readQuantity($item);
        $product->base_price = $this->readCartProductBasePrice($item);
        $product->promo_price = $this->readCartProductPromoPrice($item);

        return $product;
    }

    public function readCartProductPromoPrice($item)
    {
        $productSimple = $item->get_product();

        $gross = wc_get_price_including_tax($productSimple);
        $net = wc_get_price_excluding_tax($productSimple);

        return $this->createPrice($net, $gross);
    }

    public function readCartProductBasePrice($item)
    {
        $productSimple = $item->get_product();

        $gross = wc_get_price_including_tax($productSimple, ['price' => $productSimple->get_regular_price()]);
        $net = wc_get_price_excluding_tax($productSimple, ['price' => $productSimple->get_regular_price()]);

        return $this->createPrice($net, $gross);
    }

    public function readQuantity($item)
    {
        $quantity = $this->readStockQuantity();
        $quantity->quantity = $item->get_quantity();

        return $quantity;
    }

    public function readStockQuantity()
    {
        $quantity = new \izi\item\order\OrderQuantity();

        $quantity->quantity_type = \izi\item\Quantity::INTEGER;
        $quantity->quantity_unit = 'pcs';

        return $quantity;
    }

    public function mapProductData($cartItem)
    {
        if (!$cartItem->get_product()) {
            return;
        }
        $product = new OrderProduct();

        $product->product_id = $cartItem->get_product_id();
        if (isset($cartItem->get_product()->get_category_ids()[0])) {
            $product->product_category = $cartItem->get_product()->get_category_ids()[0];
        }
        $product->ean = $cartItem->get_product()->get_sku() ?: '0';
        $product->product_name = $cartItem->get_product()->get_name();
        $product->product_description = strip_shortcodes(strip_tags($cartItem->get_product()->get_description()));
        $product->product_link = $cartItem->get_product()->get_permalink();

        $image = wp_get_attachment_image_src(get_post_thumbnail_id($cartItem->get_product()->get_id()), 'single-post-thumbnail');
        if ($image && $image[0]) {
            $product->product_image = $image[0];
        } else {
            $product->product_image = '';
        }

        $product->variants = $this->mapProductVariables($cartItem->get_product());
        $product->product_attributes = $this->mapProductAttributes($cartItem->get_product());

        return $product;
    }

    public function mapProductAttributes($productSimple)
    {
        $array = [];

        foreach ($productSimple->get_attributes() as $attribute) {
            if ($attribute->get_visible() && $attribute->get_variation() === false) {
                foreach ($attribute->get_options() as $option) {
                    $array[] = $this->mapProductAttribute($attribute->get_name(), $option);
                }
            }
        }

        return $array;
    }

    public function mapProductAttribute($name, $value)
    {
        $productAttribute = new \izi\item\ProductAttribute();

        $productAttribute->attribute_name = $name;
        $productAttribute->attribute_value = $value;

        return $productAttribute;
    }

    public function mapProductVariables($productSimple)
    {
        $array = [];

        foreach ($productSimple->get_attributes() as $attribute) {
            if ($attribute->get_visible() && $attribute->get_variation() === true) {
                $array[] = $this->mapProductVariable($attribute);
            }
        }

        return $array;
    }

    public function mapProductVariable($attribute)
    {
        $variant = new \izi\item\Variant();

        $variant->variant_id = $attribute->get_id();
        $variant->variant_name = $attribute->get_name();
        $variant->variant_values = implode(', ', $attribute->get_options());

        $variant->variant_description = '';
        $variant->variant_type = '';

        return $variant;
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
        $delivery = new \izi\item\order\Delivery();

        $deliveryCodes = []; //explode(',', get_post_meta($this->orderId, 'delivery_codes', true));

        $additionalDeliveryOprionDictionary = [
            'PWW' => 'Paczka w Weekend',
            'COD' => 'Pobranie',
        ];
        $additionalDeliveryOprionsName = [];
        foreach ($deliveryCodes as $code) {
            if (!$code) {
                continue;
            }
            $net = esc_attr(get_option('izi_transport_price_' . strtolower($code)));
            $gross = $net * 1.23;
            $additionalDeliveryOprionsName[] = $additionalDeliveryOprionDictionary[$code];
            $delivery->delivery_options = [[
                'delivery_name' => $additionalDeliveryOprionDictionary[$code],
                'delivery_code_value' => $code,
                'delivery_option_price' => [
                    'net' => $net,
                    'gross' => number_format($gross, 2),
                    'vat' => number_format($gross - $net, 2),
                ],
            ]];
        }

        $data = $this->getOrderData();

        $delivery->delivery_type = $data && isset($data->delivery->delivery_type) ? $data->delivery->delivery_type : 'COURIER';
        $this->setDeliveryPrice($delivery);

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

    private function setDeliveryPrice(&$deliveryObject)
    {
        $wooDeliveryPrice = new PrestaDeliveryPrice();

        $cartId = CartSession::getCartIdByBasketId($this->basketId);
        $cart = new \Cart($cartId);

        $delivery = $wooDeliveryPrice->mapDelivery($cart);

        foreach ($delivery as $option) {
            if ($deliveryObject->delivery_type == $option->delivery_type) {
                $deliveryObject->delivery_price = $option->delivery_price;
                $deliveryObject->delivery_date = $option->delivery_date;
            }
        }
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
        return explode(' ', $this->deliveryDetails->phone, 2);
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

        $orderDetails->order_merchant_status_description = (new \OrderState($this->order->current_state, $this->order->id_lang))->name;
        $orderDetails->order_base_price = $this->readSummaryOrderBasePrice();
        $orderDetails->order_final_price = $this->readSummaryOrderFinalPrice();
        $orderDetails->delivery_references_list = [''];
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
     * @param $key
     *
     * @return false|string
     */
    private function getConfiguration($key)
    {
        return \Configuration::get($key, null, null, $this->order->id_shop);
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
}
