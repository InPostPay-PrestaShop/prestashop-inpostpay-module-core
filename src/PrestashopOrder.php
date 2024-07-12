<?php

namespace izi\prestashop;

use izi\item\order\OrderProduct;
use izi\item\order\OrderQuantity;
use izi\item\ProductAttribute;
use izi\prestashop\BasketApp\BasketAppClientInterface;
use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Common\Product\ProductImage;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Shipping\CarrierModuleTrackingNumberProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;

/**
 * @todo refactor...
 */
class PrestashopOrder
{
    /**
     * @var ImageRetriever
     */
    private $imageRetriever;

    private $basketId;
    private $order;
    private $customer;
    private $deliveryDetails;
    private $language;

    private $orderData;

    /**
     * @var bool
     */
    private $freeShipping;

    public function __construct(\Order $order, string $basketId)
    {
        $this->order = $order;
        $this->basketId = $basketId;

        $this->deliveryDetails = new \Address((int) $this->order->id_address_delivery);
        $this->customer = new \Customer((int) $this->order->id_customer);
        $this->language = new \Language((int) $this->order->id_lang);

        $this->imageRetriever = new ImageRetriever(\Context::getContext()->link);
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
        return array_map([$this, 'createProduct'], $this->order->getProductsDetail());
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
        $delivery->delivery_price = $this->getDeliveryPrice();
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
        if ($data = $this->getOrderData()) {
            return $data->order_details->order_comments ?? null;
        }

        return $this->order->getFirstMessage() ?: null;
    }

    public function mapOrderDetails()
    {
        $orderDetails = new \izi\item\order\OrderDetails();

        $orderDetails->order_comments = $this->readComments();
        $orderDetails->order_id = $this->order->id;
        $orderDetails->pos_id = $this->getConfiguration('INPOST_PAY_pos_id');
        $orderDetails->order_creation_date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->order->date_add)
            ->setTimezone(new \DateTimeZone(BasketAppClientInterface::DATETIME_ZONE))
            ->format(BasketAppClientInterface::DATETIME_FORMAT);
        $orderDetails->basket_id = $this->basketId;

        $orderDetails->order_merchant_status_description = $this->getStatusDescription($this->order);
        $orderDetails->order_base_price = $this->readSummaryOrderBasePrice();
        $orderDetails->order_final_price = $this->readSummaryOrderFinalPrice();
        $orderDetails->order_discount = $this->getDiscountsTotal();
        $orderDetails->delivery_references_list = $this->getTrackingNumbers();
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
        $gross = $this->order->total_paid_tax_incl;
        $net = $this->order->total_paid_tax_excl;

        if (!$this->hasFreeShippingCartRule()) {
            $gross -= $this->order->total_shipping_tax_incl;
            $net -= $this->order->total_shipping_tax_excl;
        }

        return $this->createPrice($net, $gross);
    }

    public function readPaymentType()
    {
        $data = $this->getOrderData();

        if (null !== $data && isset($data->order_details->payment_type)) {
            return $data->order_details->payment_type;
        }

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

    private function getTrackingNumbers(): array
    {
        /** @var \InPostIzi $module */
        $module = \Module::getInstanceByName('inpostizi');
        $objectManager = $module->get(ObjectManagerInterface::class);

        return (new CarrierModuleTrackingNumberProvider($objectManager))->getTrackingNumbers((int) $this->order->id);
    }

    private function getDiscountsTotal(): float
    {
        return (float) \Tools::math_round($this->order->total_discounts_tax_incl, 2);
    }

    private function createProduct(array $data): OrderProduct
    {
        $product = new OrderProduct();

        $product->product_id = sprintf('%d.%d.%d', $data['product_id'], $data['product_attribute_id'], $data['id_customization']);
        $product->ean = $data['product_ean13'];
        $product->base_price = $this->createPrice((float) $data['unit_price_tax_excl'], (float) $data['unit_price_tax_incl']);
        $product->quantity = OrderQuantity::integer((int) $data['product_quantity']);

        if (0 >= (int) $data['product_attribute_id'] || false === $pos = strrpos($data['product_name'], '(', -1)) {
            $product->product_name = $data['product_name'];
        } else {
            $product->product_name = trim(substr($data['product_name'], 0, $pos));
            $product->product_attributes = $this->getProductAttributes(substr($data['product_name'], $pos + 1, -1));
        }

        $model = new \Product((int) $data['product_id'], false, $this->order->id_lang, $this->order->id_shop);

        if (\Validate::isLoadedObject($model)) {
            $images = $this->imageRetriever->getProductImages([
                'id_product' => $model->id,
                'id_product_attribute' => $data['product_attribute_id'],
            ], $this->language);
            $imageUrl = $this->getCoverImageUrl($images);

            $product->product_category = $model->id_category_default;
            $product->product_description = $this->formatDescription((string) $model->description) ?: $this->formatDescription((string) $model->description_short);
            $product->product_link = \Context::getContext()->link->getProductLink($model, null, null, null, $this->order->id_lang, $this->order->id_shop, $data['product_attribute_id']);
            $product->product_image = $imageUrl;
            $product->additional_product_images = $this->getProductImages($images);
        }

        return $product;
    }

    private function getProductAttributes(string $attributes): array
    {
        $separator = $this->getConfiguration('PS_ATTRIBUTE_ANCHOR_SEPARATOR');
        $pattern = sprintf('/(?>(?P<attribute>[^:]+:[^:]+)%1$s(?!%1$s([^:%1$s])+:))/', $separator);

        if (!preg_match_all($pattern, $attributes . $separator, $matches)) {
            return [];
        }

        return array_map(static function (string $attribute): ProductAttribute {
            [$group, $name] = explode(':', $attribute, 2);

            $productAttribute = new ProductAttribute();
            $productAttribute->attribute_name = trim($group);
            $productAttribute->attribute_value = trim($name);

            return $productAttribute;
        }, $matches['attribute']);
    }

    private function formatDescription(string $description): string
    {
        $description = strip_tags($description);
        $description = trim(preg_replace('/\s+/', ' ', $description));

        if ('' === $description) {
            return '';
        }

        $description = htmlentities($description, ENT_HTML401, 'utf-8', false);
        $description = htmlspecialchars_decode($description);
        $description = preg_replace('/&(?:#\d+|[a-zA-Z]+);/', '', $description);

        return \Tools::substr($description, 0, 1000);
    }

    private function hasFreeShippingCartRule(): bool
    {
        if (isset($this->freeShipping)) {
            return $this->freeShipping;
        }

        foreach ($this->order->getCartRules() as $cartRule) {
            if ($cartRule['free_shipping']) {
                return $this->freeShipping = true;
            }
        }

        return $this->freeShipping = false;
    }

    private function getDeliveryPrice(): \izi\item\Price
    {
        if ($this->hasFreeShippingCartRule()) {
            return $this->createPrice(0., 0.);
        }

        return $this->createPrice(
            (float) $this->order->total_shipping_tax_excl,
            (float) $this->order->total_shipping_tax_incl
        );
    }

    private function getCoverImageUrl(array $images): ?string
    {
        if (null === $image = $this->getCoverImage($images)) {
            return null;
        }

        $image = $image['bySize']['cart_default'] ?? $image['small'];

        return $image['url'];
    }

    private function getCoverImage(array $images): ?array
    {
        foreach ($images as $image) {
            if (!empty($image['cover'])) {
                return $image;
            }
        }

        if (false !== $image = reset($images)) {
            return $image;
        }

        return null;
    }

    /**
     * @return ProductImage[]
     */
    private function getProductImages(array $images): array
    {
        return array_values(array_map(static function (array $image): ProductImage {
            $smallSize = $image['bySize']['home_default'] ?? $image['medium'];
            $normalSize = $image['bySize']['medium_default'] ?? $image['large'];

            return new ProductImage($smallSize['url'], $normalSize['url']);
        }, $images));
    }
}
