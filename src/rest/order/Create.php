<?php

namespace izi\prestashop\rest\order;

use izi\prestashop\CommandBusInterface;
use izi\prestashop\Common\Customer\InvoiceDetails;
use izi\prestashop\Common\Customer\LegalForm;
use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Common\PhoneNumber;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Event\ValidateOrderEvent;
use izi\prestashop\MerchantApi\Command\Order\UpdateCartMessageCommand;
use izi\prestashop\MerchantApi\Exception\BasketNotFoundException;
use izi\prestashop\MerchantApi\Exception\CannotCreateOrderException;
use izi\prestashop\MerchantApi\Exception\InternalServerErrorException;
use izi\prestashop\MerchantApi\Model\Order\Request\AccountInfo;
use izi\prestashop\MerchantApi\Model\Order\Request\AddressDetails;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\MerchantApi\Model\Order\Request\Delivery;
use izi\prestashop\MerchantApi\Model\Order\Request\DeliveryAddress;
use izi\prestashop\ObjectModel\Exception\InvalidDataException;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Repository\BasketSessionRepository;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use izi\prestashop\Serializer\SerializerFactory;
use izi\prestashop\Shipping\OptionalService\CashOnDeliveryHandler;
use izi\prestashop\Shipping\OptionalService\ChainHandler;
use izi\prestashop\Shipping\OptionalService\Exception\ServiceUnavailableException;
use izi\prestashop\Shipping\OptionalService\GiftWrappingHandler;
use izi\prestashop\Shipping\OptionalService\OptionalServiceHandlerInterface;
use izi\prestashop\Shipping\OptionalService\ShippingCostAdjuster;
use izi\prestashop\Shipping\OptionalService\WeekendDeliveryHandler;
use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\Validator\Product\Unrestricted;
use PrestaShop\PrestaShop\Core\Crypto\Hashing;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @deprecated
 *
 * @todo refactor
 */
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

    /**
     * @var OrdersConfigurationInterface
     */
    private $ordersConfiguration;

    /**
     * @var BasketSessionRepositoryInterface<BasketSession>
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(?\Context $context = null, ?Hashing $crypto = null, ?\PaymentModule $module = null, ShippingConfigurationInterface $shippingConfiguration = null, ?CommandBusInterface $bus = null, ?BasketSessionRepositoryInterface $repository = null, ?EventDispatcherInterface $eventDispatcher = null, ?ValidatorInterface $validator = null)
    {
        $this->context = $context ?? \Context::getContext();
        $this->crypto = $crypto ?? new Hashing();
        $this->module = $module ?? \Module::getInstanceByName('inpostizi');
        $this->shippingConfiguration = $shippingConfiguration ?? $this->module->get(ShippingConfigurationInterface::class);
        $this->bus = $bus ?? $this->module->get(CommandBusInterface::class);
        $this->ordersConfiguration = $this->module->get(OrdersConfigurationInterface::class);
        $this->repository = $repository ?? new BasketSessionRepository(SerializerFactory::create(), $this->module->get(ObjectManagerInterface::class));
        $this->eventDispatcher = $eventDispatcher ?? $this->module->get(EventDispatcherInterface::class);
        $this->validator = $validator ?? $this->module->get('inpost.izi.validator');
    }

    /**
     * @return int order identifier
     */
    public function handleRequest(CreateOrderRequest $request): int
    {
        $session = $this->getSession($request->getOrderDetails()->getBasketId());

        if (null !== $orderId = $session->getOrderId()) {
            return (int) $orderId;
        }

        $cart = $session->getBasket()->getEntity();

        if (null === $order = $this->getOrderByCart($cart)) {
            return $this->createOrder($session, $request);
        }

        if ('inpostizi' !== $order->module) {
            throw new CannotCreateOrderException($this->module->l('There already exists an order for this basket.', self::TRANSLATION_SOURCE));
        }

        $this->module->getLogger()->warning('Repeated order request for cart #{cartId}. Updating delivery emails.', [
            'cartId' => $cart->id,
            'orderId' => $order->id,
        ]);

        if (null !== $originalRequest = $session->getOrderRequest()) {
            $request = $originalRequest->withDeliveryEmails($request->getDelivery());
        }

        $this->finalizeSession($session, $request, (int) $order->id);
        $this->saveCarrierModuleData($cart->id, $request->getDelivery());

        return (int) $order->id;
    }

    private function getSession(string $basketId): BasketSession
    {
        $session = $this->repository->findByBasketId($basketId);

        if (null === $session) {
            throw BasketNotFoundException::create();
        }

        return $session;
    }

    private function getOrderByCart(\Cart $cart): ?\OrderCore
    {
        if (is_callable([\Order::class, 'getByCartId'])) {
            return \Order::getByCartId($cart->id);
        }

        $orderId = \Order::getOrderByCartId($cart->id);

        return $orderId ? new \Order($orderId) : null;
    }

    /**
     * @param BasketSession $session
     */
    private function createOrder(BasketSessionInterface $session, CreateOrderRequest $request): int
    {
        $cart = $session->getBasket()->getEntity();

        $deliveryType = $request->getDelivery()->getType();
        $serviceCodes = $request->getDelivery()->getOptionalServiceCodes();

        $shopId = $session->getShopId() ?? (int) $cart->id_shop;

        $shippingOptions = $this->shippingConfiguration->getShippingOptions($deliveryType, $shopId);

        if (DeliveryType::Digital() === $deliveryType) {
            if (!$cart->isVirtualCart()) {
                throw new CannotCreateOrderException('Digital delivery is not available for carts with physical products.');
            }

            if ([] !== $serviceCodes) {
                throw new CannotCreateOrderException(sprintf('Optional service "%s" is not available for digital delivery.', current($serviceCodes)->value));
            }

            $carrierId = null;
        } else {
            $carrierReferenceId = $shippingOptions->getCarrierMapping(...$serviceCodes)->getReferenceId();

            if (null === $carrierReferenceId || null === $carrierId = $this->getCarrierId($carrierReferenceId, $shopId)) {
                throw new InternalServerErrorException(sprintf('No valid carrier mapping configured for delivery type "%s"', $deliveryType->value));
            }
        }

        $paymentType = $request->getOrderDetails()->getPaymentType();

        $this->checkPaymentType($paymentType, $shopId);

        $customer = $this->findOrCreateCustomer($cart, $request->getAccountInfo());
        $addresses = $this->findOrCreateAddresses($customer, $request);

        $this->setUpContext($cart, $addresses, $shopId);
        $this->updateCart($cart, $carrierId, $addresses);
        $this->updateCartMessage($cart, $request);
        $this->processOptionalServices($cart, $deliveryType, $serviceCodes);

        $this->validateCart($cart, $request);

        $orderId = null;

        $this->eventDispatcher->addListener(ValidateOrderEvent::class, $listener = function (ValidateOrderEvent $event) use ($session, $request, $cart, &$orderId) {
            if ($event->getOrder()->module !== $this->module->name) {
                return;
            }

            $orderId = (int) $event->getOrder()->id;

            $this->finalizeSession($session, $request, $orderId);
            $this->saveCarrierModuleData($cart->id, $request->getDelivery());
        });

        try {
            $this->module->validateOrder(
                $cart->id,
                (int) $this->ordersConfiguration->getInitialStatusId($paymentType, $shopId),
                $cart->getOrderTotal(),
                $this->module->displayName,
                null,
                [],
                null,
                false,
                $cart->secure_key,
                $this->context->shop
            );

            return (int) $this->module->currentOrder;
        } catch (\Exception $e) {
            if (null === $orderId) {
                throw $e;
            }

            $this->module->getLogger()->error('An exception occurred after creating order #{orderId}.', [
                'orderId' => $orderId,
                'exception' => $e,
            ]);

            return $orderId;
        } finally {
            $this->eventDispatcher->removeListener(ValidateOrderEvent::class, $listener);
        }
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
    private function updateCart(\Cart $cart, ?int $carrierId, array $addresses): void
    {
        $deliveryAddressId = (int) $addresses['delivery']->id;

        if (\Tools::version_compare(_PS_VERSION_, '9.0.0') && $cart->isMultiAddressDelivery()) {
            $cart->setNoMultishipping();
        }

        $cart->updateAddressId($cart->id_address_delivery, $deliveryAddressId);
        $cart->id_address_invoice = (int) $addresses['invoice']->id;

        if (null !== $carrierId) {
            $this->setDeliveryOption($cart, [$deliveryAddressId => $carrierId . ',']);
        }

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
        } else {
            $this->fillWithAccountInfoData($address, $accountInfo);
        }

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

        $this->setAddressFields($address, $deliveryAddress->getAddress(), $deliveryAddress->getAddressDetails());
    }

    private function fillWithAccountInfoData(\AddressCore $address, AccountInfo $accountInfo): void
    {
        $clientAddress = $accountInfo->getAddress();

        $address->firstname = $accountInfo->getName();
        $address->lastname = $accountInfo->getSurname();

        $address->id_country = $this->getCountryId($clientAddress->getCountryCode());
        $address->city = $clientAddress->getCity();
        $address->postcode = $clientAddress->getPostalCode();

        $this->setAddressFields($address, $clientAddress->getAddress(), $clientAddress->getAddressDetails());
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
        if ('' !== $flat = (string) $invoiceDetails->getFlat()) {
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

    private function setAddressFields(\AddressCore $address, string $addressLine, ?AddressDetails $addressDetails): void
    {
        $requiredFields = $address->getFieldsRequiredDB();

        if (!in_array('address2', $requiredFields, true)) {
            $address->address1 = $addressLine;

            return;
        }

        if (
            null === $addressDetails
            || in_array($building = (string) $addressDetails->getBuilding(), ['', AddressDetails::BUILDING_NUMBER_PLACEHOLDER], true)
        ) {
            $address->address1 = $addressLine;
            $address->address2 = '-';

            return;
        }

        $address->address1 = $addressDetails->getStreet() ?? '-';
        $address->address2 = $building;
        if ('' !== $flat = (string) $addressDetails->getFlat()) {
            $address->address2 .= ' / ' . $flat;
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
            $this->module->getLogger()->error('Could not update the carrier module data for cart #{cartId}.', [
                'cartId' => $cartId,
                'exception' => $e,
            ]);
        }
    }

    private function validateCart(\Cart $cart, CreateOrderRequest $request): void
    {
        $products = $cart->getProducts();

        if ([] === $products) {
            throw new CannotCreateOrderException($this->context->getTranslator()->trans('Cart is empty', [], 'Shop.Notifications.Error'));
        }

        $this->validateCartRules($cart);
        $this->checkMinimalPurchaseAmount($cart);
        $this->checkCartTotal($cart, $request);

        foreach ($products as $product) {
            if ($product['minimal_quantity'] > $product['cart_quantity']) {
                throw new CannotCreateOrderException($this->context->getTranslator()->trans('The minimum purchase order quantity for the product %product% is %quantity%.', ['%product%' => $product['name'], '%quantity%' => $product['minimal_quantity']], 'Shop.Notifications.Error'));
            }

            $violations = $this->validator->validate($product, new Unrestricted([
                'shopId' => (int) $product['id_shop'],
                'deliveryType' => $request->getDelivery()->getType(),
            ]));

            if (0 === $violations->count()) {
                continue;
            }

            if (Unrestricted::DELIVERY_DISALLOWED_ERROR === $violations->get(0)->getCode()) {
                throw new CannotCreateOrderException($this->module->l('The selected delivery option is not available.', self::TRANSLATION_SOURCE));
            }

            throw new CannotCreateOrderException($this->context->getTranslator()->trans('This product (%product%) is no longer available.', ['%product%' => $product['name']], 'Shop.Notifications.Error'));
        }

        if (true === $product = $cart->checkQuantities(true)) {
            return;
        }

        if ($product['active']) {
            throw new CannotCreateOrderException(sprintf($this->module->l('You can\'t proceed with your order, the product is not available in this quantity: %s', self::TRANSLATION_SOURCE), $product['name']));
        }

        throw new CannotCreateOrderException($this->context->getTranslator()->trans('This product (%product%) is no longer available.', ['%product%' => $product['name']], 'Shop.Notifications.Error'));
    }

    private function validateCartRules(\Cart $cart): void
    {
        /** @var \CartRule $cartRule */
        foreach ($cart->getCartRules() as ['obj' => $cartRule]) {
            if (!$error = $cartRule->checkValidity($this->context, true)) {
                continue;
            }

            throw new CannotCreateOrderException(sprintf($this->module->l('Voucher %s is no longer available: %s'), $cartRule->code ?: $cartRule->name, $error));
        }
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

    private function checkCartTotal(\Cart $cart, CreateOrderRequest $request): void
    {
        $details = $request->getOrderDetails();

        $orderTotal = $cart->getOrderTotal();
        $basketPrice = $details->getBasketPrice()->getGross();
        $epsilon = $details->getCurrency()->getSmallestUnitAmount() / 2.;

        if (abs($orderTotal - $basketPrice) >= $epsilon) {
            throw new CannotCreateOrderException($this->module->l('Basket price has changed. Please review your order.', self::TRANSLATION_SOURCE));
        }
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
    private function setUpContext(\Cart $cart, array $addresses, int $shopId): void
    {
        if ($currencyId = \Currency::getIdByIsoCode('PLN')) {
            $cart->id_currency = $currencyId;
        }

        $this->context->cart = $cart;
        $this->context->shop = new \Shop($shopId);
        $this->context->customer = new \Customer($cart->id_customer);
        $this->context->cart->setTaxCalculationMethod();
        $this->context->currency = \Currency::getCurrencyInstance($cart->id_currency);
        $this->context->language = new \Language($cart->id_lang);

        $this->context->getTranslator()->setLocale($this->context->language->locale);

        \Shop::setContext(\Shop::CONTEXT_SHOP, $shopId);

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
        $availablePaymentOptions = $this->ordersConfiguration->getAvailablePaymentOptions($shopId);

        if ([] === $availablePaymentOptions) {
            return;
        }

        if (in_array($paymentType, $availablePaymentOptions, true)) {
            return;
        }

        throw new CannotCreateOrderException($this->module->l('The selected payment method is not available.', self::TRANSLATION_SOURCE));
    }

    /**
     * @param BasketSession $session
     */
    private function finalizeSession(BasketSessionInterface $session, CreateOrderRequest $request, int $orderId): void
    {
        $orderConfirmationUrl = $this->getOrderConfirmationUrl($session->getBasket()->getEntity(), $orderId);

        $session->finalize((string) $orderId, $orderConfirmationUrl, $request);
        $this->repository->persist($session);
    }

    private function getOrderConfirmationUrl(\Cart $cart, int $orderId): string
    {
        return \Context::getContext()->link->getPageLink('order-confirmation', null, $cart->id_lang, [
            'id_cart' => $cart->id,
            'id_module' => $this->module->id,
            'id_order' => $orderId,
            'key' => $cart->secure_key,
        ]);
    }

    private function processOptionalServices(\Cart $cart, DeliveryType $deliveryType, array $serviceCodes): void
    {
        if (DeliveryType::Digital() === $deliveryType) {
            return;
        }

        try {
            $handler = $this->module->get(OptionalServiceHandlerInterface::class);
        } catch (ServiceNotFoundException $e) {
            $config = new PrestaShopConfiguration(new Configuration());
            $costAdjuster = new ShippingCostAdjuster($this->context, $this->shippingConfiguration, $config);

            $handler = new ChainHandler([
                new GiftWrappingHandler($config, $this->module->get(ObjectManagerInterface::class), new LegacyTranslator($this->module->name)),
                new CashOnDeliveryHandler($costAdjuster),
                new WeekendDeliveryHandler($costAdjuster),
            ]);
        }

        foreach (ServiceCode::cases() as $serviceCode) {
            try {
                $handler->handle($cart, $serviceCode->value, $deliveryType, in_array($serviceCode, $serviceCodes, true));
            } catch (ServiceUnavailableException $e) {
                throw new CannotCreateOrderException($e->getMessage());
            }
        }
    }
}
