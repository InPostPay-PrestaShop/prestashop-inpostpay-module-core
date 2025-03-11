<?php

namespace izi\prestashop;

use izi\prestashop\Builder\PriceFactory;
use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Common\Currency;
use izi\prestashop\Common\Customer\AccountInfo;
use izi\prestashop\Common\Customer\ClientAddress;
use izi\prestashop\Common\Customer\InvoiceDetails;
use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\OptionalService;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Common\Order\Consent;
use izi\prestashop\Common\Order\Product;
use izi\prestashop\Common\Order\Quantity;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Common\PhoneNumber;
use izi\prestashop\Common\Price;
use izi\prestashop\Common\Product\ProductAttribute;
use izi\prestashop\Common\Product\ProductImage;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\MerchantApi\Model\Order\Response\Delivery;
use izi\prestashop\MerchantApi\Model\Order\Response\Order;
use izi\prestashop\MerchantApi\Model\Order\Response\OrderDetails;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Order\Address\AddressDataMapper;
use izi\prestashop\Product\Util\AttributeListParser;
use izi\prestashop\Shipping\CarrierModuleTrackingNumberProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;

/**
 * @internal
 * @deprecated
 *
 * @todo refactor...
 */
class PrestashopOrder
{
    /**
     * @var ImageRetriever
     */
    private $imageRetriever;

    /**
     * @var \InPostIzi
     */
    private $module;

    private $basketId;
    private $order;
    private $customer;
    private $deliveryDetails;
    private $language;

    /**
     * @var CreateOrderRequest|null should be required but might not be available if e.g. {@see \PaymentModule::validateOrder()} had thrown an exception
     */
    private $orderData;

    /**
     * @var bool
     */
    private $freeShipping;

    /**
     * @var ProductConfigurationInterface
     */
    private $productConfiguration;

    /**
     * @var AddressDataMapper
     */
    private $addressDataMapper;

    /**
     * @var AttributeListParser
     */
    private $attributeListParser;

    public function __construct(\Order $order, string $basketId, ?CreateOrderRequest $orderData = null)
    {
        $this->order = $order;
        $this->basketId = $basketId;
        $this->orderData = $orderData;

        $this->module = \Module::getInstanceByName('inpostizi');

        $this->deliveryDetails = new \Address((int) $this->order->id_address_delivery);
        $this->customer = $this->order->getCustomer();
        $this->language = new \Language((int) $this->order->id_lang);
        $this->productConfiguration = $this->module->get(ProductConfigurationInterface::class);

        $this->imageRetriever = new ImageRetriever(\Context::getContext()->link);
        $this->addressDataMapper = new AddressDataMapper();
    }

    public static function getOrder(\Order $order, string $basketId, ?CreateOrderRequest $request = null): Order
    {
        return (new self($order, $basketId, $request))->mapOrder();
    }

    public function mapOrder(): Order
    {
        return new Order(
            $this->mapOrderDetails(),
            $this->mapAccountInfo(),
            $this->mapDelivery(),
            $this->mapProducts(),
            $this->mapConsents(),
            $this->mapInvoiceDetails()
        );
    }

    /**
     * @return Consent[]
     */
    public function mapConsents(): array
    {
        if (null !== $this->orderData) {
            return $this->orderData->getConsents();
        }

        $config = json_decode($this->getConfiguration('INPOST_PAY_CONSENTS'), true) ?? [];

        if ([] === $config) {
            return [];
        }

        $consents = [];

        foreach ($config as $consent) {
            $date = \DateTimeImmutable::createFromFormat(\DateTime::RFC3339, $consent['dateUpdated']);

            $consents[] = new Consent(
                $consent['link']['id'],
                false === $date ? '0' : (string) $date->getTimestamp(),
                $consent['requirementType'] !== ConsentRequirementType::Optional()->value
            );
        }

        return $consents;
    }

    public function mapAccountInfo(): AccountInfo
    {
        if (null !== $this->orderData) {
            return AccountInfo::fromOrderRequestData($this->orderData->getAccountInfo());
        }

        return new AccountInfo(
            (string) $this->customer->firstname,
            (string) $this->customer->lastname,
            $this->mapPhoneNumber(),
            (string) $this->customer->email,
            $this->mapClientAddress()
        );
    }

    /**
     * @return Product[]
     */
    public function mapProducts(): array
    {
        return array_map([$this, 'createProduct'], $this->order->getProductsDetail());
    }

    public function mapClientAddress(): ClientAddress
    {
        return new ClientAddress(
            (string) \Country::getIsoById($this->deliveryDetails->id_country),
            $this->deliveryDetails->address1 . ' ' . $this->deliveryDetails->address2,
            (string) $this->deliveryDetails->city,
            (string) $this->deliveryDetails->postcode
        );
    }

    public function mapInvoiceDetails(): ?InvoiceDetails
    {
        if (null === $this->orderData) {
            return null;
        }

        return $this->orderData->getInvoiceDetails();
    }

    public function mapDelivery(): Delivery
    {
        if (null !== $this->orderData) {
            $delivery = $this->orderData->getDelivery();
            $deliveryType = $delivery->getType();
            $deliveryCodes = $delivery->getOptionalServiceCodes();
            $email = $delivery->getEmail() ?? $this->customer->email;
            $phoneNumber = $delivery->getPhoneNumber() ?? $this->mapPhoneNumber();
            $deliveryPoint = $delivery->getPoint();
            $courierNote = $delivery->getCourierNote();
        } else {
            $deliveryType = DeliveryType::Courier();
            $deliveryCodes = [];
            $email = $this->customer->email;
            $phoneNumber = $this->mapPhoneNumber();
            $deliveryPoint = null;
            $courierNote = $this->deliveryDetails->other;
        }

        $deliveryDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->order->date_add)
            ->modify('+2 days')
            ->setTime(12, 0);

        $deliveryOptions = array_map(static function (ServiceCode $code): OptionalService {
            $serviceNameDictionary = [
                'PWW' => 'Paczka w Weekend',
                'COD' => 'Pobranie',
            ];

            return new OptionalService(
                $serviceNameDictionary[$code->value] ?? $code->value,
                $code,
                PriceFactory::create(0., 0.) // TODO: get the actually used prices
            );
        }, $deliveryCodes);

        return new Delivery(
            $deliveryType,
            $deliveryDate,
            $this->getDeliveryPrice(),
            $deliveryOptions,
            $email,
            $phoneNumber,
            $deliveryPoint,
            $this->addressDataMapper->mapDeliveryAddress($this->deliveryDetails),
            $courierNote
        );
    }

    public function mapPhoneNumber(): PhoneNumber
    {
        return $this->addressDataMapper->mapPhoneNumber($this->deliveryDetails);
    }

    private function readComments(): ?string
    {
        if (null !== $this->orderData) {
            return $this->orderData->getOrderDetails()->getOrderComments();
        }

        return $this->order->getFirstMessage() ?: null;
    }

    public function mapOrderDetails(): OrderDetails
    {
        return new OrderDetails(
            (string) $this->order->id,
            (string) $this->getConfiguration('INPOST_PAY_pos_id'),
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->order->date_add),
            $this->basketId,
            (string) $this->getStatusDescription($this->order),
            $this->readPaymentType(),
            $this->readSummaryOrderBasePrice(),
            $this->readSummaryOrderFinalPrice(),
            Currency::Pln(),
            $this->getTrackingNumbers(),
            $this->readComments(),
            $this->getDiscountsTotal(),
            $this->order->reference
        );
    }

    public function readSummaryOrderFinalPrice(): Price
    {
        return PriceFactory::create(
            (float) $this->order->total_paid_tax_excl,
            (float) $this->order->total_paid_tax_incl
        );
    }

    public function readSummaryOrderBasePrice(): Price
    {
        $gross = (float) $this->order->total_paid_tax_incl;
        $net = (float) $this->order->total_paid_tax_excl;

        if (!$this->hasFreeShippingCartRule()) {
            $gross -= $this->order->total_shipping_tax_incl;
            $net -= $this->order->total_shipping_tax_excl;
        }

        return PriceFactory::create($net, $gross);
    }

    public function readPaymentType(): PaymentType
    {
        if (null === $this->orderData) {
            return PaymentType::Card();
        }

        return $this->orderData->getOrderDetails()->getPaymentType();
    }

    /**
     * @return false|string
     */
    private function getConfiguration(string $key, bool $lang = false)
    {
        $languageId = $lang ? (int) $this->order->id_lang : null;

        return \Configuration::get($key, $languageId, null, $this->order->id_shop);
    }

    private function getStatusDescription(\Order $order): string
    {
        $orderStateId = (int) $order->current_state;
        $config = $this->getConfiguration('INPOST_PAY_OS_DESCRIPTION_MAP', true);
        $map = $config ? json_decode($config, true) : [];

        return $map[$orderStateId] ?? (new \OrderState($orderStateId, $order->id_lang))->name;
    }

    private function getTrackingNumbers(): array
    {
        $objectManager = $this->module->get(ObjectManagerInterface::class);

        return (new CarrierModuleTrackingNumberProvider($objectManager))->getTrackingNumbers((int) $this->order->id);
    }

    private function getDiscountsTotal(): float
    {
        return (float) \Tools::math_round($this->order->total_discounts_tax_incl, 2);
    }

    private function createProduct(array $data): Product
    {
        if (0 >= (int) $data['product_attribute_id'] || false === $pos = strrpos($data['product_name'], '(', -1)) {
            $productName = $data['product_name'];
            $attributes = [];
        } else {
            $productName = trim(substr($data['product_name'], 0, $pos));
            $attributes = $this->getProductAttributes(substr($data['product_name'], $pos + 1, -1));
        }

        $model = new \Product((int) $data['product_id'], false, $this->order->id_lang, $this->order->id_shop);

        if (\Validate::isLoadedObject($model)) {
            $category = $model->id_category_default;
            $description = $this->formatDescription((string) $model->description) ?: $this->formatDescription((string) $model->description_short);
            $link = \Context::getContext()->link->getProductLink($model, null, null, null, $this->order->id_lang, $this->order->id_shop, $data['product_attribute_id']);

            $images = $this->imageRetriever->getProductImages([
                'id_product' => $model->id,
                'id_product_attribute' => $data['product_attribute_id'],
            ], $this->language);

            $imageUrl = $this->getCoverImageUrl($images);
            $additionalImages = $this->getProductImages($images);
        } else {
            $category = $description = $link = $imageUrl = null;
            $additionalImages = [];
        }

        return new Product(
            sprintf('%d.%d.%d', $data['product_id'], $data['product_attribute_id'], $data['id_customization']),
            $productName,
            PriceFactory::create((float) $data['unit_price_tax_excl'], (float) $data['unit_price_tax_incl']),
            Quantity::integer((int) $data['product_quantity']),
            $category,
            $data['product_ean13'],
            $description,
            $link,
            $imageUrl,
            $attributes,
            [],
            $additionalImages
        );
    }

    private function getProductAttributes(string $attributes): array
    {
        return array_map(static function (array $attribute) {
            return new ProductAttribute($attribute['group'], $attribute['name']);
        }, $this->getAttributeListParser()->parse($attributes, (int) $this->order->id_shop));
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

    private function getDeliveryPrice(): Price
    {
        if ($this->hasFreeShippingCartRule()) {
            return PriceFactory::create(0., 0.);
        }

        return PriceFactory::create(
            (float) $this->order->total_shipping_tax_excl,
            (float) $this->order->total_shipping_tax_incl
        );
    }

    private function getImageTypeNameByImageTypeId(int $idImageType, string $defaultValue): ?string
    {
        $imageType = $idImageType ? new \ImageType($idImageType) : null;

        return $imageType && \Validate::isLoadedObject($imageType) ? $imageType->name : $defaultValue;
    }

    private function getCoverImageUrl(array $images): ?string
    {
        if (null === $image = $this->getCoverImage($images)) {
            return null;
        }

        $idImageType = $this->productConfiguration->getNormalImageTypeId((int) $this->order->id_shop);

        $image = $image['bySize'][$this->getImageTypeNameByImageTypeId($idImageType, 'cart_default')] ?? $image['small'];

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
        if ([] === $images) {
            return [];
        }

        $images = array_slice($images, 0, 10);

        $idImageTypeSmall = $this->productConfiguration->getSmallImageTypeId((int) $this->order->id_shop);
        $idImageTypeLarge = $this->productConfiguration->getLargeImageTypeId((int) $this->order->id_shop);
        $smallFormatName = $this->getImageTypeNameByImageTypeId($idImageTypeSmall, 'home_default');
        $largeFormatName = $this->getImageTypeNameByImageTypeId($idImageTypeLarge, 'medium_default');

        return array_map(static function (array $image) use ($smallFormatName, $largeFormatName): ProductImage {
            $smallSize = $image['bySize'][$smallFormatName] ?? $image['medium'];
            $normalSize = $image['bySize'][$largeFormatName] ?? $image['large'];

            return new ProductImage($smallSize['url'], $normalSize['url']);
        }, $images);
    }

    private function getAttributeListParser(): AttributeListParser
    {
        return $this->attributeListParser ?? $this->attributeListParser = new AttributeListParser(
            new PrestaShopConfiguration(new Configuration()),
            \Context::getContext(),
            _PS_VERSION_
        );
    }
}
