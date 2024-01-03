<?php

namespace izi\prestashop\rest\order;

use izi\item\order\InvoiceDetails;
use izi\prestashop\CartSession;
use izi\prestashop\MerchantApi\Exception\BasketNotFoundException;
use izi\prestashop\MerchantApi\Exception\CannotCreateOrderException;
use izi\prestashop\MerchantApi\Exception\InternalServerErrorException;
use izi\prestashop\traits\CarrierFinderTrait;
use PrestaShop\PrestaShop\Core\Crypto\Hashing;

class Create
{
    use CarrierFinderTrait;

    private const TRANSLATION_SOURCE = 'create';

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var Hashing
     */
    private $crypto;

    /**
     * @var \InPostIzi
     */
    private $module;

    public function __construct(\Context $context = null, Hashing $crypto = null, \PaymentModule $module = null)
    {
        $this->context = $context ?? \Context::getContext();
        $this->crypto = $crypto ?? new Hashing();
        $this->module = $module ?? \Module::getInstanceByName('inpostizi');
    }

    /**
     * @param object $data
     *
     * @return int order identifier
     */
    public function handleRequest($data): int
    {
        $cart = $this->getCart($data->order_details->basket_id);

        if ($order = $this->getOrderByCart($cart)) {
            if ('inpostizi' !== $order->module) {
                throw new CannotCreateOrderException($this->module->l('There already exists an order for this basket.', self::TRANSLATION_SOURCE));
            }

            return $order->id;
        }

        return $this->createOrder($cart, $data);
    }

    private function getCart(string $basketId): \Cart
    {
        $cartId = CartSession::getCartIdByBasketId($basketId);

        if (!$cartId || !\Validate::isLoadedObject($cart = new \Cart($cartId))) {
            throw BasketNotFoundException::create();
        }

        return $cart;
    }

    private function getOrderByCart(\Cart $cart): ?\Order
    {
        if (is_callable([\Order::class, 'getByCartId'])) {
            return \Order::getByCartId($cart->id);
        }

        $orderId = \Order::getOrderByCartId($cart->id);

        return $orderId ? new \Order($orderId) : null;
    }

    private function createOrder(\Cart $cart, $data): int
    {
        if (null === $carrierId = $this->getCarrierId($data->delivery->delivery_type)) {
            throw new InternalServerErrorException(sprintf('No valid carrier mapping configured for delivery type "%s"', $data->delivery->delivery_type));
        }

        $customer = $this->getOrCreateCustomer($cart, $data->account_info);

        $this->setUpContext($cart);
        $this->updateCart($cart, $data, $customer, $carrierId);
        $this->adjustHandlingCost($data);
        $this->validateCart($cart);

        $this->module->validateOrder(
            $cart->id,
            (int) \Configuration::get('INPOST_PAY_INITIAL_OS_ID'),
            $cart->getOrderTotal(),
            $this->module->displayName,
            null,
            [],
            null,
            false,
            $cart->secure_key
        );

        $link = \Context::getContext()->link->getPageLink('order-confirmation', null, $cart->id_lang, [
            'id_cart' => $cart->id,
            'id_module' => $this->module->id,
            'id_order' => $this->module->currentOrder,
            'key' => $cart->secure_key,
        ]);

        CartSession::setRedirectUrl($data->order_details->basket_id, $link);
        CartSession::setOrderData($data->order_details->basket_id, $this->module->currentOrder, json_encode($data));

        $this->saveCarrierModuleData($cart->id, $data->delivery);

        return $this->module->currentOrder;
    }

    private function updateCart(\Cart $cart, $data, \Customer $customer, int $carrierId): void
    {
        $deliveryAddressId = $this->createDeliveryAddress($data->account_info, $customer, $data->delivery->delivery_address ?? null);

        $cart->updateAddressId($cart->id_address_delivery, $deliveryAddressId);
        $this->setDeliveryOption($cart, [$deliveryAddressId => $carrierId . ',']);

        if (isset($data->invoice_details)) {
            $cart->id_address_invoice = $this->createInvoiceAddress($data->invoice_details, $data->account_info, $customer);
        } else {
            $cart->id_address_invoice = $deliveryAddressId;
        }

        if (!$cart->update()) {
            throw new InternalServerErrorException('Could not update cart data.');
        }

        $this->updateCartMessage($cart->id, $data);
    }

    private function setDeliveryOption(\Cart $cart, array $deliveryOption): void
    {
        $cart->setDeliveryOption($deliveryOption);

        if ($deliveryOption === $cart->getDeliveryOption(null, true)) {
            return;
        }

        throw new CannotCreateOrderException($this->module->l('The selected delivery option is not available.', self::TRANSLATION_SOURCE));
    }

    private function getCountryId(string $code): ?int
    {
        return \Country::getByIso(strtoupper($code)) ?: null;
    }

    private function createDeliveryAddress($accountInfo, \Customer $customer, $deliveryAddress = null): int
    {
        $address = new \Address();

        $address->id_customer = $customer->id;
        $address->phone = $accountInfo->phone_number->country_prefix . ' ' . $accountInfo->phone_number->phone;

        if (null !== $deliveryAddress) {
            $this->fillWithDeliveryAddressData($address, $deliveryAddress);
        }

        $address->firstname = $address->firstname ?? $accountInfo->name;
        $address->lastname = $address->lastname ?? $accountInfo->surname;
        $address->id_country = $address->id_country ?? $this->getCountryId($accountInfo->client_address->country_code);
        $address->city = $address->city ?? $accountInfo->client_address->city;
        $address->postcode = $address->postcode ?? $accountInfo->client_address->postal_code;
        $address->address1 = $address->address1 ?? $accountInfo->client_address->address;

        if ($addressId = $this->getExistingAddressId($customer, $address)) {
            return $addressId;
        }

        $address->alias = \Tools::substr($address->address1, 0, 32);

        if (!$address->add()) {
            throw new InternalServerErrorException('Could not create delivery address.');
        }

        return $address->id;
    }

    private function fillWithDeliveryAddressData(\Address $address, $deliveryAddress): void
    {
        if (isset($deliveryAddress->name)) {
            $name = preg_split('/\s+/', $deliveryAddress->name, 2, PREG_SPLIT_NO_EMPTY);
            $address->firstname = $name[0];
            $address->lastname = $name[1] ?? '-';
        }

        $address->id_country = $this->getCountryId($deliveryAddress->country_code);
        $address->city = $deliveryAddress->city ?? null;
        $address->postcode = $deliveryAddress->postcode ?? null;
        $address->address1 = $deliveryAddress->address ?? null;
    }

    private function createInvoiceAddress($invoiceDetails, $accountInfo, \Customer $customer): int
    {
        $address = new \Address();
        $address->id_customer = $customer->id;
        $address->firstname = !empty($invoiceDetails->name) ? $invoiceDetails->name : $accountInfo->name;
        $address->lastname = !empty($invoiceDetails->surname) ? $invoiceDetails->surname : $accountInfo->surname;
        $address->id_country = $this->getCountryId($invoiceDetails->country_code);
        $address->city = $invoiceDetails->city;
        $address->postcode = $invoiceDetails->postal_code;
        $address->address1 = $invoiceDetails->street;
        $address->address2 = $invoiceDetails->building;
        if (!empty($invoiceDetails->flat)) {
            $address->address2 .= ' / ' . $invoiceDetails->flat;
        }

        if (InvoiceDetails::LEGAL_FORM_COMPANY === $invoiceDetails->legal_form) {
            $address->company = $invoiceDetails->company_name;
            if (!empty($invoiceDetails->tax_id_prefix)) {
                $address->vat_number = sprintf('%s %s', $invoiceDetails->tax_id_prefix, $invoiceDetails->tax_id);
            } else {
                $address->vat_number = $invoiceDetails->tax_id;
            }
        }

        if ($addressId = $this->getExistingAddressId($customer, $address, ['phone'])) {
            return $addressId;
        }

        $address->alias = \Tools::substr($address->address1 . ' ' . $address->address2, 0, 32);

        if (!$address->add()) {
            throw new InternalServerErrorException('Could not create invoice address.');
        }

        return $address->id;
    }

    private function getExistingAddressId(\Customer $customer, \Address $address, array $ignoreFields = []): ?int
    {
        if ($customer->is_guest) {
            return null;
        }

        if (!$addresses = $customer->getAddresses((int) \Configuration::get('PS_LANG_DEFAULT'))) {
            return null;
        }

        foreach ($addresses as $data) {
            if ($this->isSameAddress($address, $data, $ignoreFields)) {
                return (int) $data['id_address'];
            }
        }

        return null;
    }

    private function isSameAddress(\Address $address, array $data, array $ignoreFields): bool
    {
        $comparedFields = array_diff([
            'firstname',
            'lastname',
            'id_country',
            'city',
            'postcode',
            'address1',
            'address2',
            'company',
            'vat_number',
            'phone',
        ], $ignoreFields);

        foreach ($comparedFields as $field) {
            if ($data[$field] != $address->{$field}) {
                return false;
            }
        }

        return true;
    }

    private function getOrCreateCustomer(\Cart $cart, $accountInfo): \Customer
    {
        $customer = new \Customer($cart->id_customer);

        if (!$customer->is_guest && \Validate::isLoadedObject($customer)) {
            return $customer;
        }

        $customer->email = $accountInfo->mail;
        $customer->firstname = $accountInfo->name;
        $customer->lastname = $accountInfo->surname;

        if (!\Validate::isLoadedObject($customer)) {
            $password = \Tools::passwdGen(8, 'RANDOM');

            $customer->id_lang = $cart->id_lang;
            $customer->passwd = $this->crypto->hash($password);
            $customer->is_guest = true;

            if (!$customer->add()) {
                throw new InternalServerErrorException('Could not create customer account.');
            }
        } elseif (!$customer->update()) {
            throw new InternalServerErrorException('Could not update customer account.');
        }

        $cart->id_customer = $customer->id;
        $cart->secure_key = $customer->secure_key;

        return $customer;
    }

    private function adjustHandlingCost($data): void
    {
        if (0. === $deliveryOptionsCost = $this->getAdditionalDeliveryOptionsCost($data->delivery)) {
            return;
        }

        $handlingCost = (float) \Configuration::get('PS_SHIPPING_HANDLING');
        \Configuration::set('PS_SHIPPING_HANDLING', $handlingCost + $deliveryOptionsCost);
        \Cache::clean('getPackageShippingCost_*');
        \Cart::resetStaticCache();
    }

    private function getAdditionalDeliveryOptionsCost($deliveryData): float
    {
        if (
            !isset($deliveryData->delivery_codes) ||
            !is_array($deliveryData->delivery_codes) ||
            [] === $deliveryData->delivery_codes
        ) {
            return 0.;
        }

        $additionalCostsPln = 0.;
        foreach ($deliveryData->delivery_codes as $optionCode) {
            $configKey = sprintf('INPOST_PAY_payment_%s_%s', strtolower($deliveryData->delivery_type), strtolower($optionCode));
            $additionalCostsPln += (float) str_replace(',', '.', \Configuration::get($configKey));
        }

        if (0. >= $additionalCostsPln) {
            return 0.;
        }

        $defaultCurrency = \Currency::getDefaultCurrency();
        if ('PLN' === $defaultCurrency->iso_code) {
            return $additionalCostsPln;
        }

        return \Tools::convertPriceFull($additionalCostsPln, $this->context->currency, $defaultCurrency);
    }

    private function updateCartMessage(int $cartId, $data): void
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

    private function saveCarrierModuleData(int $cartId, $delivery): void
    {
        if (!class_exists(\InPostCartChoiceModel::class)) {
            return;
        }

        try {
            $model = new \InPostCartChoiceModel($cartId);
            $model->id = $cartId;
            $model->service = 'APM' === $delivery->delivery_type ? 'inpost_locker_standard' : 'inpost_courier_standard';
            if ('APM' === $delivery->delivery_type) {
                $model->point = $delivery->delivery_point;
            }
            $model->email = $delivery->mail;
            $model->phone = $delivery->phone_number->phone;
            $model->save();
        } catch (\Exception $e) {
            $this->module->getLogger()->error('Could not save shipment data: {error}', [
                'error' => $e,
            ]);
        }
    }

    private function validateCart(\Cart $cart): void
    {
        $products = $cart->getProducts();

        if ([] === $products) {
            throw new CannotCreateOrderException($this->context->getTranslator()->trans('Cart is empty', [], 'Shop.Notifications.Error'));
        }

        $this->checkMinimalPurchaseAmount($cart);

        foreach ($products as $product) {
            if ($product['minimal_quantity'] > $product['cart_quantity']) {
                throw new CannotCreateOrderException($this->context->getTranslator()->trans('The minimum purchase order quantity for the product %product% is %quantity%.', [
                    '%product%' => $product['name'],
                    '%quantity%' => $product['minimal_quantity'],
                ], 'Shop.Notifications.Error'));
            }
        }

        if (true === $product = $cart->checkQuantities(true)) {
            return;
        }

        if ($product['active']) {
            throw new CannotCreateOrderException($this->context->getTranslator()->trans('%product% is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.', [
                '%product%' => $product['name'],
            ], 'Shop.Notifications.Error'));
        }

        throw new CannotCreateOrderException($this->context->getTranslator()->trans('This product (%product%) is no longer available.', [
            '%product%' => $product['name'],
        ], 'Shop.Notifications.Error'));
    }

    private function checkMinimalPurchaseAmount(\Cart $cart): void
    {
        if (0. >= $minimalPurchase = $this->getMinimalPurchaseAmount()) {
            return;
        }

        $productsTotalExcludingTax = $cart->getOrderTotal(false, \Cart::ONLY_PRODUCTS);
        if ($minimalPurchase <= $productsTotalExcludingTax) {
            return;
        }

        throw new CannotCreateOrderException($this->context->getTranslator()->trans('A minimum shopping cart total of %amount% (tax excl.) is required to validate your order. Current cart total is %total% (tax excl.).', [
            '%amount%' => $this->formatPrice($minimalPurchase),
            '%total%' => $this->formatPrice($productsTotalExcludingTax),
        ], 'Shop.Theme.Checkout'));
    }

    private function getMinimalPurchaseAmount(): float
    {
        $minimalPurchase = (float) \Tools::convertPrice((float) \Configuration::get('PS_PURCHASE_MINIMUM'), $this->context->currency);

        \Hook::exec('overrideMinimalPurchasePrice', [
            'minimalPurchase' => &$minimalPurchase,
        ]);

        return $minimalPurchase;
    }

    private function formatPrice(float $price): string
    {
        if (!is_callable([\Tools::class, 'getContextLocale'])) {
            return \Tools::displayPrice($price, $this->context->currency);
        }

        return \Tools::getContextLocale($this->context)->formatPrice($price, 'PLN');
    }

    private function setUpContext(\Cart $cart): void
    {
        if ($currencyId = \Currency::getIdByIsoCode('PLN')) {
            $cart->id_currency = $currencyId;
        }

        $this->context->cart = $cart;
        $this->context->shop = new \Shop($cart->id_shop);
        $this->context->customer = new \Customer($cart->id_customer);
        $this->context->cart->setTaxCalculationMethod();
        $this->context->currency = \Currency::getCurrencyInstance($cart->id_currency);
        $this->context->language = new \Language($cart->id_lang);

        $this->context->getTranslator()->setLocale($this->context->language->locale);

        \Shop::setContext(\Shop::CONTEXT_SHOP, $cart->id_shop);
    }
}
