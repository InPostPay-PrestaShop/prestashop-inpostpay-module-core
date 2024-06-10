<?php

namespace izi\prestashop\rest\order;

use izi\prestashop\CartSession;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Common\Customer\InvoiceDetails;
use izi\prestashop\Common\Customer\LegalForm;
use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Common\Order\Delivery;
use izi\prestashop\Common\Order\DeliveryAddress;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Common\PhoneNumber;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;
use izi\prestashop\Configuration\OrdersConfiguration;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\MerchantApi\Command\Order\UpdateCartMessageCommand;
use izi\prestashop\MerchantApi\Exception\BasketNotFoundException;
use izi\prestashop\MerchantApi\Exception\CannotCreateOrderException;
use izi\prestashop\MerchantApi\Exception\InternalServerErrorException;
use izi\prestashop\MerchantApi\Model\Order\Request\AccountInfo;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\ObjectModel\Exception\InvalidDataException;
use izi\prestashop\Serializer\SerializerFactory;
use PrestaShop\PrestaShop\Core\Crypto\Hashing;

class Create
{
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

    /**
     * @var ShippingConfigurationInterface
     */
    private $shippingConfiguration;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    public function __construct(?\Context $context = null, ?Hashing $crypto = null, ?\PaymentModule $module = null, ShippingConfigurationInterface $shippingConfiguration = null, ?CommandBusInterface $bus = null)
    {
        $this->context = $context ?? \Context::getContext();
        $this->crypto = $crypto ?? new Hashing();
        $this->module = $module ?? \Module::getInstanceByName('inpostizi');
        $this->shippingConfiguration = $shippingConfiguration ?? $this->module->get(ShippingConfigurationInterface::class);
        $this->bus = $bus ?? $this->module->get(CommandBusInterface::class);
    }

    /**
     * @param object $data
     *
     * @return int order identifier
     */
    public function handleRequest($data): int
    {
        $request = $this->normalizeRequest($data);

        $cart = $this->getCart($request->getOrderDetails()->getBasketId());

        if ($order = $this->getOrderByCart($cart)) {
            if ('inpostizi' !== $order->module) {
                throw new CannotCreateOrderException($this->module->l('There already exists an order for this basket.', self::TRANSLATION_SOURCE));
            }

            return $order->id;
        }

        return $this->createOrder($cart, $request);
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

    private function createOrder(\Cart $cart, CreateOrderRequest $request): int
    {
        $deliveryType = $request->getDelivery()->getType();
        $serviceCodes = $request->getDelivery()->getOptionalServiceCodes();

        $shopId = (int) $cart->id_shop;

        $shippingOptions = $this->shippingConfiguration->getShippingOptions($deliveryType, $shopId);
        $carrierReferenceId = $shippingOptions->getCarrierMapping(...$serviceCodes)->getReferenceId();

        if (null === $carrierReferenceId || null === $carrierId = $this->getCarrierId($carrierReferenceId, $shopId)) {
            throw new InternalServerErrorException(sprintf('No valid carrier mapping configured for delivery type "%s"', $deliveryType->value));
        }

        $this->checkPaymentType($request->getOrderDetails()->getPaymentType(), $shopId);

        $customer = $this->findOrCreateCustomer($cart, $request->getAccountInfo());
        $addresses = $this->findOrCreateAddresses($customer, $request);

        $this->setUpContext($cart, $addresses);
        $this->updateCart($cart, $carrierId, $addresses);
        $this->updateCartMessage($cart, $request);
        $this->adjustHandlingCost($shippingOptions, $serviceCodes);
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

        $basketId = $request->getOrderDetails()->getBasketId();

        CartSession::setRedirectUrl($basketId, $link);
        CartSession::setOrderData($basketId, $this->module->currentOrder, json_encode($request));

        $this->saveCarrierModuleData($cart->id, $request->getDelivery());

        return $this->module->currentOrder;
    }

    private function findOrCreateAddresses(\Customer $customer, CreateOrderRequest $request): array
    {
        $accountInfo = $request->getAccountInfo();

        $deliveryAddress = $this->findOrCreateDeliveryAddress($accountInfo, $customer, $request->getDelivery()->getAddress());
        if (null !== $invoiceDetails = $request->getInvoiceDetails()) {
            $invoiceAddress = $this->findOrCreateInvoiceAddress($invoiceDetails, $accountInfo, $customer);
        } else {
            $invoiceAddress = $deliveryAddress;
        }

        return [
            'delivery' => $deliveryAddress,
            'invoice' => $invoiceAddress,
        ];
    }

    /**
     * @param array{delivery: \AddressCore, invoice: \AddressCore} $addresses
     */
    private function updateCart(\Cart $cart, int $carrierId, array $addresses): void
    {
        $deliveryAddressId = (int) $addresses['delivery']->id;

        $cart->updateAddressId($cart->id_address_delivery, $deliveryAddressId);
        $this->setDeliveryOption($cart, [$deliveryAddressId => $carrierId . ',']);
        $cart->id_address_invoice = (int) $addresses['invoice']->id;

        if (!$cart->update()) {
            throw new InternalServerErrorException('Could not update cart data.');
        }
    }

    private function setDeliveryOption(\Cart $cart, array $deliveryOption): void
    {
        $cart->setDeliveryOption($deliveryOption);

        if ($deliveryOption === $cart->getDeliveryOption(null, true)) {
            return;
        }

        throw new CannotCreateOrderException($this->module->l('The selected delivery option is not available.', self::TRANSLATION_SOURCE));
    }

    private function getCountryId(string $code): int
    {
        if (0 >= $countryId = (int) \Country::getByIso(strtoupper($code))) {
            throw new CannotCreateOrderException(sprintf($this->module->l('Selected country (%s) is not available.', self::TRANSLATION_SOURCE), $code));
        }

        return $countryId;
    }

    private function findOrCreateDeliveryAddress(AccountInfo $accountInfo, \Customer $customer, ?DeliveryAddress $deliveryAddress = null): \AddressCore
    {
        $address = $this->createAddress();

        $address->id_customer = $customer->id;

        $this->setRequiredPhoneNumbers($address, $accountInfo);
        if (!$address->phone && !$address->phone_mobile) {
            $address->phone = $this->formatPhoneNumber($accountInfo->getPhoneNumber());
        }

        if (null !== $deliveryAddress) {
            $this->fillWithDeliveryAddressData($address, $deliveryAddress);
        }

        $clientAddress = $accountInfo->getAddress();

        $address->firstname = $address->firstname ?? $accountInfo->getName();
        $address->lastname = $address->lastname ?? $accountInfo->getSurname();
        $address->id_country = $address->id_country ?? $this->getCountryId($clientAddress->getCountryCode());
        $address->city = $address->city ?? $clientAddress->getCity();
        $address->postcode = $address->postcode ?? $clientAddress->getPostalCode();
        $address->address1 = $address->address1 ?? $clientAddress->getAddress();

        if ($existingAddress = $this->findExistingAddress($customer, $address)) {
            return $existingAddress;
        }

        $address->alias = \Tools::substr($address->address1, 0, 32);

        if (true !== $validationResult = $address->validateFields(false, true)) {
            throw new CannotCreateOrderException(sprintf($this->module->l('Delivery address is not valid: %s', self::TRANSLATION_SOURCE), $validationResult));
        }

        if (!$address->add()) {
            throw new InternalServerErrorException('Could not create delivery address.');
        }

        return $address;
    }

    private function fillWithDeliveryAddressData(\AddressCore $address, DeliveryAddress $deliveryAddress): void
    {
        $name = preg_split('/\s+/', $deliveryAddress->getName(), 2, PREG_SPLIT_NO_EMPTY);

        $address->firstname = $name[0];
        $address->lastname = $name[1] ?? '-';

        $address->id_country = $this->getCountryId($deliveryAddress->getCountryCode());
        $address->city = $deliveryAddress->getCity();
        $address->postcode = $deliveryAddress->getPostalCode();
        $address->address1 = $deliveryAddress->getAddress();
    }

    private function findOrCreateInvoiceAddress(InvoiceDetails $invoiceDetails, AccountInfo $accountInfo, \Customer $customer): \AddressCore
    {
        $address = $this->createAddress();

        $this->setRequiredPhoneNumbers($address, $accountInfo);

        $address->id_customer = $customer->id;
        $address->firstname = $invoiceDetails->getName() ?? $accountInfo->getName();
        $address->lastname = $invoiceDetails->getSurname() ?? $accountInfo->getSurname();
        $address->id_country = $this->getCountryId($invoiceDetails->getCountryCode());
        $address->city = $invoiceDetails->getCity();
        $address->postcode = $invoiceDetails->getPostalCode();
        $address->address1 = $invoiceDetails->getStreet();
        $address->address2 = $invoiceDetails->getBuilding();
        if ($flat = $invoiceDetails->getFlat()) {
            $address->address2 .= ' / ' . $flat;
        }

        if (LegalForm::Company() === $invoiceDetails->getLegalForm()) {
            $address->company = $invoiceDetails->getCompanyName();
            if ($prefix = $invoiceDetails->getTaxIdPrefix()) {
                $address->vat_number = sprintf('%s %s', $prefix, $invoiceDetails->getTaxId());
            } else {
                $address->vat_number = $invoiceDetails->getTaxId();
            }
        }

        if ($existingAddress = $this->findExistingAddress($customer, $address, ['phone', 'phone_mobile'])) {
            return $existingAddress;
        }

        $address->alias = \Tools::substr($address->address1 . ' ' . $address->address2, 0, 32);

        if (true !== $validationResult = $address->validateFields(false, true)) {
            throw new CannotCreateOrderException(sprintf($this->module->l('Invoice address is not valid: %s', self::TRANSLATION_SOURCE), $validationResult));
        }

        if (!$address->add()) {
            throw new InternalServerErrorException('Could not create invoice address.');
        }

        return $address;
    }

    private function createAddress(): \AddressCore
    {
        return class_exists(\CustomerAddress::class) ? new \CustomerAddress() : new \Address();
    }

    private function setRequiredPhoneNumbers(\AddressCore $address, AccountInfo $accountInfo): void
    {
        if ([] === $requiredFields = $address->getFieldsRequiredDB()) {
            return;
        }

        $phoneNumber = $this->formatPhoneNumber($accountInfo->getPhoneNumber());

        foreach (['phone', 'phone_mobile'] as $field) {
            if (in_array($field, $requiredFields, true)) {
                $address->{$field} = $phoneNumber;
            }
        }
    }

    private function formatPhoneNumber(PhoneNumber $phoneNumber): string
    {
        return $phoneNumber->getCountryPrefix() . ' ' . $phoneNumber->getPhone();
    }

    private function findExistingAddress(\Customer $customer, \AddressCore $address, array $ignoreFields = []): ?\AddressCore
    {
        if ($customer->is_guest) {
            return null;
        }

        if (!$addresses = $customer->getAddresses((int) \Configuration::get('PS_LANG_DEFAULT'))) {
            return null;
        }

        foreach ($addresses as $data) {
            if ($this->isSameAddress($address, $data, $ignoreFields)) {
                $existingAddress = $this->createAddress();
                $existingAddress->hydrate($data);

                return $existingAddress;
            }
        }

        return null;
    }

    private function isSameAddress(\AddressCore $address, array $data, array $ignoreFields): bool
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
            'phone_mobile',
        ], $ignoreFields);

        foreach ($comparedFields as $field) {
            if ($data[$field] != $address->{$field}) {
                return false;
            }
        }

        return true;
    }

    private function findOrCreateCustomer(\Cart $cart, AccountInfo $accountInfo): \Customer
    {
        $customer = new \Customer($cart->id_customer);

        if (!$customer->is_guest && \Validate::isLoadedObject($customer)) {
            return $customer;
        }

        $customer->email = $accountInfo->getEmail();
        $customer->firstname = $accountInfo->getName();
        $customer->lastname = $accountInfo->getSurname();

        if ($newCustomer = !\Validate::isLoadedObject($customer)) {
            $password = \Tools::passwdGen(8, 'RANDOM');

            $customer->id_lang = $cart->id_lang;
            $customer->passwd = $this->crypto->hash($password);
            $customer->is_guest = true;
        }

        if (true !== $validationResult = $customer->validateFields(false, true)) {
            throw new CannotCreateOrderException(sprintf($this->module->l('Customer data is not valid: %s', self::TRANSLATION_SOURCE), $validationResult));
        }

        if (!$customer->save()) {
            throw new InternalServerErrorException($newCustomer ? 'Could not create customer account.' : 'Could not update customer account.');
        }

        $cart->id_customer = $customer->id;
        $cart->secure_key = $customer->secure_key;

        return $customer;
    }

    private function adjustHandlingCost(ShippingOptions $shippingOptions, array $serviceCodes): void
    {
        if (0. === $deliveryOptionsCost = $this->getAdditionalDeliveryOptionsCost($shippingOptions, $serviceCodes)) {
            return;
        }

        $handlingCost = (float) \Configuration::get('PS_SHIPPING_HANDLING');
        \Configuration::set('PS_SHIPPING_HANDLING', $handlingCost + $deliveryOptionsCost);
        \Cache::clean('getPackageShippingCost_*');
        \Cart::resetStaticCache();
    }

    /**
     * @param ServiceCode[] $serviceCodes
     */
    private function getAdditionalDeliveryOptionsCost(ShippingOptions $shippingOptions, array $serviceCodes): float
    {
        if ([] === $serviceCodes) {
            return 0.;
        }

        $additionalCostsPln = 0.;
        foreach ($serviceCodes as $serviceCode) {
            $serviceOptions = $shippingOptions->getServiceOptions($serviceCode);

            if (null === $serviceOptions || null === $additionalCost = $serviceOptions->getAdditionalCost()) {
                continue;
            }

            $additionalCostsPln += $additionalCost;
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

    private function updateCartMessage(\Cart $cart, CreateOrderRequest $request): void
    {
        try {
            $this->bus->handle(new UpdateCartMessageCommand($cart, $request));
        } catch (InvalidDataException $e) {
            throw new CannotCreateOrderException($this->module->l('Order comments are not valid.', self::TRANSLATION_SOURCE));
        } catch (\Exception $e) {
            throw new InternalServerErrorException('Could not save order comments.', 0, $e);
        }
    }

    private function saveCarrierModuleData(int $cartId, Delivery $delivery): void
    {
        if (!class_exists(\InPostCartChoiceModel::class)) {
            return;
        }

        try {
            $model = new \InPostCartChoiceModel($cartId);

            if ($newModel = 0 === (int) $model->id) {
                $model->id = $cartId;
            }

            $deliveryType = $delivery->getType();
            $phoneNumber = $delivery->getPhoneNumber();

            if (DeliveryType::Apm() === $deliveryType) {
                $model->service = 'inpost_locker_standard';
                $model->point = $delivery->getPoint();
            } else {
                $model->service = 'inpost_courier_standard';
            }
            $model->email = $delivery->getEmail();
            $model->phone = null === $phoneNumber ? null : $phoneNumber->getPhone();

            $newModel ? $model->add() : $model->update();
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
                throw new CannotCreateOrderException($this->context->getTranslator()->trans('The minimum purchase order quantity for the product %product% is %quantity%.', ['%product%' => $product['name'], '%quantity%' => $product['minimal_quantity']], 'Shop.Notifications.Error'));
            }
        }

        if (true === $product = $cart->checkQuantities(true)) {
            return;
        }

        if ($product['active']) {
            throw new CannotCreateOrderException($this->context->getTranslator()->trans('%product% is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.', ['%product%' => $product['name']], 'Shop.Notifications.Error'));
        }

        throw new CannotCreateOrderException($this->context->getTranslator()->trans('This product (%product%) is no longer available.', ['%product%' => $product['name']], 'Shop.Notifications.Error'));
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

        throw new CannotCreateOrderException($this->context->getTranslator()->trans('A minimum shopping cart total of %amount% (tax excl.) is required to validate your order. Current cart total is %total% (tax excl.).', ['%amount%' => $this->formatPrice($minimalPurchase), '%total%' => $this->formatPrice($productsTotalExcludingTax)], 'Shop.Theme.Checkout'));
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

    /**
     * @param array{delivery: \AddressCore, invoice: \AddressCore} $addresses
     */
    private function setUpContext(\Cart $cart, array $addresses): void
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

        $taxAddress = 'id_address_invoice' === \Configuration::get(PrestaShopConfiguration::TAX_ADDRESS_TYPE)
            ? $addresses['invoice']
            : $addresses['delivery'];

        $this->context->country = new \Country($taxAddress->id_country, $cart->id_lang);

        if (!$this->context->country->active) {
            throw new CannotCreateOrderException(sprintf($this->module->l('Selected country (%s) is not available.', self::TRANSLATION_SOURCE), $this->context->country->name));
        }
    }

    private function getCarrierId(int $referenceId, int $shopId): ?int
    {
        if (0 >= $referenceId) {
            return null;
        }

        if (false === $carrier = \Carrier::getCarrierByReference($referenceId)) {
            return null;
        }

        if (!$carrier->active || !$carrier->isAssociatedToShop($shopId)) {
            return null;
        }

        return (int) $carrier->id;
    }

    private function checkPaymentType(PaymentType $paymentType, int $shopId): void
    {
        $configuration = new Configuration();
        $availablePaymentOptions = (new OrdersConfiguration($configuration))->getAvailablePaymentOptions($shopId);

        if (in_array($paymentType, $availablePaymentOptions, true)) {
            return;
        }

        throw new CannotCreateOrderException($this->module->l('The selected payment method is not available.', self::TRANSLATION_SOURCE));
    }

    private function normalizeRequest($request): CreateOrderRequest
    {
        if ($request instanceof CreateOrderRequest) {
            return $request;
        }

        return SerializerFactory::create()->deserialize(json_encode($request), CreateOrderRequest::class, 'json');
    }
}
