<?php

namespace izi\prestashop;

use izi\item\BasketProduct;
use izi\item\order\OrderProduct;

class PrestashopOrder
{
    private $orderId;
    private $basketId;
    private $order;
    private $customer;
    private $deliveryDetails;

    private $orderBasePriceNet = 0.0;
    private $orderBasePriceGross = 0.0;
    private $orderBasePriceVat = 0.0;

    private $orderPromoPriceNet = 0.0;
    private $orderPromoPriceGross = 0.0;
    private $orderPromoPriceVat = 0.0;

    public function __construct($orderId, $basketId)
    {
        $this->orderId = $orderId;
        $this->basketId = $basketId;
        $this->order = new \Order($orderId);

        $this->deliveryDetails = new \Address((int) ($this->order->id_address_delivery));
        $this->customer = new \Customer((int) ($this->deliveryDetails->id_customer));
    }

    public static function getOrder($orderId, $basketId)
    {
        $prestashopOrder = new self($orderId, $basketId);
        $order = new \izi\item\order\Order();

        $order->account_info = $prestashopOrder->mapAccountInfo();
        $order->invoice_details = $prestashopOrder->mapInvoiceDetails();
        $order->delivery = $prestashopOrder->mapDelivery();
        $order->products = $prestashopOrder->mapProducts();
        $order->order_details = $prestashopOrder->mapOrderDetails($order->delivery->delivery_price);
        $order->consents = $prestashopOrder->mapConsents();

        return $order;
    }

    public function mapConsents()
    {
        $data = CartSession::getOrderData($this->orderId);
        if ($data) {
            $data = json_decode($data);

            return $data->consents;
        }

        $consents = [];
        $context = \Context::getContext();

        $selectedRequired = explode(',', \Configuration::get('INPOST_PAY_terms_options_required'));
        $requiredText = \Configuration::get('INPOST_PAY_terms_options_required_text');

        $selectedRequiredOnce = explode(',', \Configuration::get('INPOST_PAY_terms_options_required_once'));
        $requiredOnceText = \Configuration::get('INPOST_PAY_terms_options_required_once_text');

        $selectedAdditional = explode(',', \Configuration::get('INPOST_PAY_terms_options_required_additional'));
        $requiredAdditionalText = \Configuration::get('INPOST_PAY_terms_options_required_additional_text');

        foreach (\CMS::getCMSPages((int) \Configuration::get('PS_LANG_DEFAULT'), 1, true) as $page) {
            $link = $context->link->getCMSLink($page['id_cms'], $page['link_rewrite']);
            if (in_array($link, $selectedRequired)) {
                $consents[] = [
                    'consent_id' => count($consents) + 1,
                    'consent_link' => $link,
                    'consent_description' => $requiredText,
                    'consent_version' => 1,
                    'requirement_type' => 'REQUIRED_ALWAYS',
                ];
            } elseif (in_array($link, $selectedRequiredOnce)) {
                $consents[] = [
                    'consent_id' => count($consents) + 1,
                    'consent_link' => $link,
                    'consent_description' => $requiredOnceText,
                    'consent_version' => 1,
                    'requirement_type' => 'REQUIRED_ONCE',
                ];
            } elseif (in_array($link, $selectedAdditional)) {
                $consents[] = [
                    'consent_id' => count($consents) + 1,
                    'consent_link' => $link,
                    'consent_description' => $requiredAdditionalText,
                    'consent_version' => 1,
                    'requirement_type' => 'OPTIONAL',
                ];
            }
        }

        return $consents;
    }

    public function mapAccountInfo()
    {
        $order = CartSession::getOrderData($this->orderId);
        if ($order) {
            $order = json_decode($order);
            if (isset($order->account_info)) {
                return $order->account_info;
            }
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

        $basket = json_decode($basket);

        foreach ($basket->products as $product) {
            $this->orderBasePriceNet += $product->base_price->net;
            $this->orderBasePriceGross += $product->base_price->gross;
            $this->orderBasePriceVat += $product->base_price->vat;

            $this->orderPromoPriceNet += $product->promo_price->net;
            $this->orderPromoPriceGross += $product->promo_price->gross;
            $this->orderPromoPriceVat += $product->promo_price->vat;
        }

        return array_map(static function (BasketProduct $product): OrderProduct {
            return $product->asOrderProduct();
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
        $quantity = $item->get_quantity();
        $price = new \izi\item\Price();

        $priceIncludingTax = wc_get_price_including_tax($productSimple);
        $priceExcludingTax = wc_get_price_excluding_tax($productSimple);
        $vat = $priceExcludingTax - $priceExcludingTax;

        $price->net = number_format($priceExcludingTax, 2);
        $price->gross = number_format($priceIncludingTax, 2);
        $price->vat = number_format($vat, 2);

        $this->orderPromoPriceNet += $priceExcludingTax * $quantity;
        $this->orderPromoPriceGross += $priceIncludingTax * $quantity;
        $this->orderPromoPriceVat += $vat * $quantity;

        return $price;
    }

    public function readCartProductBasePrice($item)
    {
        $productSimple = $item->get_product();
        $quantity = $item->get_quantity();
        $price = new \izi\item\Price();

        $priceIncludingTax = wc_get_price_including_tax($productSimple, ['price' => $productSimple->get_regular_price()]);
        $priceExcludingTax = wc_get_price_excluding_tax($productSimple, ['price' => $productSimple->get_regular_price()]);
        $vat = $priceExcludingTax - $priceExcludingTax;

        $price->gross = number_format($priceIncludingTax, 2);
        $price->net = number_format($priceExcludingTax, 2);
        $price->vat = number_format($vat, 2);

        $this->orderBasePriceNet += $priceExcludingTax * $quantity;
        $this->orderBasePriceGross += $priceIncludingTax * $quantity;
        $this->orderBasePriceVat += $vat * $quantity;

        return $price;
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

        $clientAddress->country_code = 'PL'; //$this->order->get_billing_country();
        $clientAddress->address = $this->deliveryDetails->address1 . ' ' . $this->deliveryDetails->address2;
        $clientAddress->city = $this->deliveryDetails->city;
        $clientAddress->postal_code = $this->deliveryDetails->postcode;

        return $clientAddress;
    }

    public function mapInvoiceDetails()
    {
        $data = CartSession::getOrderData($this->orderId);
        if ($data) {
            $data = json_decode($data);
            if (isset($data->invoice_details)) {
                return $data->invoice_details;
            }
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

        $data = CartSession::getOrderData($this->orderId);
        if ($data) {
            $data = json_decode($data);
        }

        $delivery->delivery_type = $data->delivery->delivery_type;
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

        $cartId = CartSession::getSessionId($this->basketId);
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
        $order = CartSession::getOrderData($this->orderId);
        if ($order) {
            $order = json_decode($order);
            if (isset($order->delivery, $order->delivery->delivery_address)) {
                return $order->delivery->delivery_address;
            } elseif (isset($order->account_info)) {
                $deliveryAddress = new \izi\item\order\DeliveryAddress();
                $deliveryAddress->name = $order->account_info->name . ' ' . $order->account_info->surname;
                $deliveryAddress->country_code = $order->account_info->client_address->country_code;
                $deliveryAddress->address = $order->account_info->client_address->address;
                $deliveryAddress->city = $order->account_info->client_address->city;
                $deliveryAddress->postal_code = $order->account_info->client_address->postal_code;

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
        $array = explode(' ', $this->order->getCustomer()->getAddresses((int) \Configuration::get('PS_LANG_DEFAULT'))['0']['phone']);

        return [array_shift($array), implode(' ', $array)];
    }

    private function readComments()
    {
        return ''; //$this->order->get_customer_note();
    }

    public function mapOrderDetails($deliveryPrice)
    {
        $orderDetails = new \izi\item\order\OrderDetails();

        $orderDetails->order_comments = $this->readComments();
        $orderDetails->order_id = $this->orderId;
        $orderDetails->pos_id = \Configuration::get('INPOST_PAY_pos_id');
        $orderDetails->order_creation_date = date("Y-m-d\TH:i:s.000\Z", strtotime($this->order->date_add));
        $orderDetails->basket_id = $this->basketId;

        $orderDetails->order_merchant_status_description = 'Oczekuje na płatność'; //StatusTranslator::paymentStatusToText($orderDetails->payment_status);
        $orderDetails->order_base_price = $this->readSummaryOrderPromoPrice();
//        $orderDetails->order_promo_price = $this->readSummaryOrderPromoPrice();
//        $order_promo_price = $this->readSummaryOrderPromoPrice();
        $orderDetails->order_final_price = $this->readSummaryOrderFinalPrice($orderDetails->order_base_price, $deliveryPrice);
        $orderDetails->delivery_references_list = [''];
        $orderDetails->currency = 'PLN'; //$this->order->get_order_currency();
        $orderDetails->payment_type = $this->readPaymentType();

        return $orderDetails;
    }

    public function readSummaryOrderFinalPrice($promoPrice, $deliveryPrice)
    {
        $price = new \izi\item\Price();

        $couponsWorth = 0.0;

        $price->gross = number_format($this->order->total_paid_tax_incl, 2, '.', '');
        $price->net = number_format($this->order->total_paid_tax_excl, 2, '.', '');
        $price->vat = number_format($this->order->total_paid_tax_incl - $this->order->total_paid_tax_excl, 2, '.', '');

        return $price;
    }

    public function readSummaryOrderPromoPrice()
    {
        $price = new \izi\item\Price();

        $gross = $this->order->total_paid_tax_incl - $this->order->total_shipping_tax_incl;
        $price->gross = number_format($gross, 2, '.', '');
        $net = $this->order->total_paid_tax_excl - $this->order->total_shipping_tax_excl;
        $price->net = number_format($net, 2, '.', '');
        $price->vat = number_format($gross - $net, 2, '.', '');

        return $price;
    }

    public function readSummaryOrderBasePrice()
    {
        $price = new \izi\item\Price();

        $price->gross = number_format($this->orderBasePriceGross, 2, '.', '');
        $price->net = number_format($this->orderBasePriceNet, 2, '.', '');
        $price->vat = number_format($this->orderBasePriceVat, 2, '.', '');

        return $price;
    }

    public function getBasketHash()
    {
        $basket = (new \izi\Remote($this->orderId))->basketGet();
        if (isset($basket->summary)) {
            return $basket->summary->basket_hash;
        }

        return '';
    }

    public function readPaymentType()
    {
        return 'BLIK_CODE'; //get_post_meta($this->orderId, 'izi_payment_type', true);
    }
}
