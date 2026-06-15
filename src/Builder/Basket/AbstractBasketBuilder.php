<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Builder\PriceFactory;
use izi\prestashop\Common\Basket\AvailablePromotion;
use izi\prestashop\Common\Basket\Consent;
use izi\prestashop\Common\Basket\ConsentLink;
use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Common\Basket\DeliveryOption;
use izi\prestashop\Common\Basket\Notice;
use izi\prestashop\Common\Basket\Product;
use izi\prestashop\Common\Basket\Quantity;
use izi\prestashop\Common\Basket\Summary;
use izi\prestashop\Common\Currency;
use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Common\Price;
use izi\prestashop\Common\Product\DeliveryProduct;
use izi\prestashop\Common\Product\DeliveryRelatedProducts;
use izi\prestashop\Common\Product\ProductAttribute;
use izi\prestashop\Common\Product\ProductType;
use izi\prestashop\Common\Product\ProductVariant;
use izi\prestashop\Common\PromoCode;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\ConsentsConfigurationInterface;
use izi\prestashop\Configuration\DTO;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\ContextManager;
use izi\prestashop\Product\Image\ImageUrlsProvider;
use izi\prestashop\Product\Image\ImageUrlsProviderInterface;
use izi\prestashop\Product\Price\BatchLowestPriceProviderInterface;
use izi\prestashop\Product\Price\LowestPriceProviderInterface;
use izi\prestashop\Product\Price\LowestPriceQuery;
use izi\prestashop\Product\Price\NullLowestPriceProvider;
use izi\prestashop\Product\ReferenceId;
use izi\prestashop\Product\Util\AttributeListParser;
use izi\prestashop\Product\Util\DescriptionFormatter;
use izi\prestashop\PromoCode\AvailablePromotionsProviderInterface;
use izi\prestashop\PromoCode\CartRulePromoCodeProvider;
use izi\prestashop\PromoCode\NullAvailablePromotionsProvider;
use izi\prestashop\PromoCode\PromoCodeProviderInterface;
use izi\prestashop\Validator\Product\Unrestricted;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\Cart\Calculator;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @todo split into separate services
 */
abstract class AbstractBasketBuilder implements BasketBuilderInterface
{
    /**
     * @var \Cart
     */
    private $cart;

    /**
     * @var ContextManager
     */
    private $contextManager;

    /**
     * @var ConsentsConfigurationInterface
     */
    private $consentsConfiguration;

    /**
     * @var ProductConfigurationInterface
     */
    private $productConfiguration;

    /**
     * @var DeliveryFactory
     */
    private $deliveryFactory;

    /**
     * @var ProductDeliveryFactory
     */
    private $productDeliveryFactory;

    /**
     * @var ImageRetriever|null
     */
    private $imageRetriever;

    /**
     * @var LowestPriceProviderInterface
     */
    private $lowestPriceProvider;

    /**
     * @var PromoCodeProviderInterface
     */
    private $promoCodeProvider;

    /**
     * @var AvailablePromotionsProviderInterface
     */
    private $availablePromotionsProvider;

    /**
     * @var AttributeListParser
     */
    private $attributeListParser;

    /**
     * @var ImageUrlsProvider
     */
    private $imageProvider;

    /**
     * @var ValidatorInterface|null
     */
    private $validator;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expirationDate;

    /**
     * @var Notice|null
     */
    private $notice;

    /**
     * @var string|null
     */
    private $additionalInformation;

    /**
     * @var DeliveryOption[]|null
     */
    private $availableDeliveryOptions;

    /**
     * @var Summary
     */
    private $cartSummary;

    /**
     * @var int
     */
    private $shopId;

    /**
     * @var Price
     */
    private $promoPrice;

    /**
     * @var bool
     */
    private $hasDigitalDelivery = false;

    public function __construct(
        \Cart $cart,
        ContextManager $contextManager,
        ConsentsConfigurationInterface $consentsConfiguration,
        ProductConfigurationInterface $productConfiguration,
        DeliveryFactory $deliveryFactory,
        ProductDeliveryFactory $deliveryRelatedProductFactory,
        ?ImageRetriever $imageRetriever = null,
        ?LowestPriceProviderInterface $lowestPriceProvider = null,
        ?PromoCodeProviderInterface $promoCodeProvider = null,
        ?AvailablePromotionsProviderInterface $availablePromotionsProvider = null,
        ?ValidatorInterface $validator = null
    ) {
        $this->cart = $cart;
        $this->contextManager = $contextManager;
        $this->consentsConfiguration = $consentsConfiguration;
        $this->deliveryFactory = $deliveryFactory;
        $this->productConfiguration = $productConfiguration;
        $this->productDeliveryFactory = $deliveryRelatedProductFactory;
        $this->imageRetriever = $imageRetriever;
        $this->lowestPriceProvider = $lowestPriceProvider ?? new NullLowestPriceProvider();
        $this->promoCodeProvider = $promoCodeProvider ?? CartRulePromoCodeProvider::create();
        $this->availablePromotionsProvider = $availablePromotionsProvider ?? new NullAvailablePromotionsProvider();
        $this->validator = $validator;
        $this->shopId = (int) $this->cart->id_shop;
    }

    public function setShopId(int $shopId): void
    {
        $this->shopId = $shopId;
    }

    /**
     * @return static
     */
    public function setExpirationDate(?\DateTimeImmutable $expirationDate): BasketBuilderInterface
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * @return static
     */
    public function setNotice(?Notice $notice): BasketBuilderInterface
    {
        $this->notice = $notice;

        return $this;
    }

    /**
     * @return static
     */
    public function setAdditionalInformation(?string $info): BasketBuilderInterface
    {
        $this->additionalInformation = $info;

        return $this;
    }

    public function build()
    {
        try {
            $this->contextManager->changeContext($this->cart, [
                'shop_id' => $this->shopId,
            ]);

            $cartProducts = $this->cart->getProducts(false, false, null, true, true);
            $deliveryOptions = $this->getAvailableDeliveryOptions($this->cart);
            $relatedProducts = $this->getRelatedProducts($cartProducts);
            $products = $this->createCartProducts($cartProducts);

            return $this->doBuild(
                $this->createSummary($products),
                $deliveryOptions,
                $products,
                $this->getConsents(),
                array_values($this->promoCodeProvider->getPromoCodes($this->cart)),
                $relatedProducts,
                array_values($this->availablePromotionsProvider->getAvailablePromotions($this->cart))
            );
        } finally {
            $this->contextManager->restoreContext();
        }
    }

    /**
     * @param DeliveryOption[] $delivery
     * @param Product[] $products
     * @param Consent[] $consents
     * @param PromoCode[] $promoCodes
     * @param Product[] $relatedProducts
     * @param AvailablePromotion[] $availablePromotions
     */
    abstract protected function doBuild(Summary $summary, array $delivery, array $products, array $consents, array $promoCodes, array $relatedProducts/*, array $availablePromotions = []*/);

    /**
     * @return Product[]
     */
    private function createCartProducts(array $products): array
    {
        if (!$this->lowestPriceProvider instanceof BatchLowestPriceProviderInterface) {
            return array_map([$this, 'createCartProduct'], $products);
        }

        $queries = $this->getLowestPriceQueries($products, static function (array $product): bool {
            return (bool) $product['reduction_applies'];
        });

        try {
            $this->lowestPriceProvider->preparePrices(...$queries);

            return array_map([$this, 'createCartProduct'], $products);
        } finally {
            $this->lowestPriceProvider->reset();
        }
    }

    private function createCartProduct(array $product): Product
    {
        $model = new \Product($product['id_product'], false, $this->cart->id_lang, $product['id_shop']);

        return $this->createProduct(
            $model,
            $product,
            $this->createQuantity($product, (int) $product['cart_quantity']),
            $this->getProductBasePrice($product),
            $this->getCartProductPromoPrice($product)
        );
    }

    private function getProductBasePrice(array $product): Price
    {
        $gross = $product['price_without_reduction'] ?? $this->calculateProductPrice(
            (int) $product['id_product'],
            (int) $product['id_product_attribute'],
            true,
            false,
            (int) $product['cart_quantity'],
            (int) $product['id_customization'],
            (int) $product['id_shop']
        );

        $net = $product['price_without_reduction_without_tax'] ?? $this->calculateProductPrice(
            (int) $product['id_product'],
            (int) $product['id_product_attribute'],
            false,
            false,
            (int) $product['cart_quantity'],
            (int) $product['id_customization'],
            (int) $product['id_shop']
        );

        return PriceFactory::create((float) $net, (float) $gross);
    }

    private function getCartProductPromoPrice(array $product): ?Price
    {
        if (!$product['reduction_applies']) {
            return null;
        }

        $gross = $product['price_with_reduction'] ?? $this->calculateProductPrice(
            (int) $product['id_product'],
            (int) $product['id_product_attribute'],
            true,
            true,
            (int) $product['cart_quantity'],
            (int) $product['id_customization'],
            (int) $product['id_shop']
        );

        $net = $product['price_with_reduction_without_tax'] ?? $this->calculateProductPrice(
            (int) $product['id_product'],
            (int) $product['id_product_attribute'],
            false,
            true,
            (int) $product['cart_quantity'],
            (int) $product['id_customization'],
            (int) $product['id_shop']
        );

        return PriceFactory::create((float) $net, (float) $gross);
    }

    private function createProduct(\Product $model, array $product, Quantity $quantity, Price $basePrice, ?Price $promoPrice, bool $related = false): Product
    {
        $combinationId = array_key_exists('id_product_attribute', $product) ? (int) $product['id_product_attribute'] : null;
        $customizationId = array_key_exists('id_customization', $product) ? (int) $product['id_customization'] : 0;
        $shopId = array_key_exists('id_shop', $product) ? (int) $product['id_shop'] : null;

        $category = $model->id_category_default ?: $model->getDefaultCategory();
        $description = DescriptionFormatter::formatDescription($model);
        $link = $this->contextManager->getContext()->link->getProductLink($model, null, null, null, $this->cart->id_lang, $shopId, $combinationId);
        $imageUrls = $this->getImageProvider()->getImageUrls((int) $model->id, $combinationId);

        return new Product(
            (string) ReferenceId::create($model->id, $combinationId, $customizationId),
            $product['name'],
            $basePrice,
            $quantity,
            is_array($category) ? (string) current($category) : (string) $category,
            $product['ean13'] ?? $model->ean13,
            $description,
            $link,
            $imageUrls->getMainImageUrl(),
            $promoPrice,
            null === $promoPrice ? null : $this->getLowestPrice((int) $model->id, $combinationId, $shopId),
            $this->getProductAttributes($product),
            null,
            $imageUrls->getAdditionalImages(),
            $related ? null : $this->getDeliveryProduct($model, $promoPrice ?? $basePrice, $quantity, (float) $product['weight'], $product),
            $related ? $this->getDeliveryRelatedProducts($model, $promoPrice ?? $basePrice, $quantity) : null,
            $model->is_virtual ? ProductType::Digital() : ProductType::Physical()
        );
    }

    /**
     * @return ProductAttribute[]
     */
    private function getProductAttributes(array $product): array
    {
        return array_map([$this, 'createProductAttribute'], $this->getAttributes($product));
    }

    private function createProductAttribute(array $attribute): ProductAttribute
    {
        return new ProductAttribute($attribute['group'], $attribute['name']);
    }

    private function getAttributes(array $product): array
    {
        if (!isset($product['id_product_attribute']) || 0 >= (int) $product['id_product_attribute']) {
            return [];
        }

        $attributes = $product['attributes'] ?? null;

        if (is_array($attributes)) {
            return array_values($attributes);
        }

        /* @see \CartCore::cacheSomeAttributesLists() */
        if (!is_string($attributes) || '' === $attributes) {
            return [];
        }

        return $this->getAttributeListParser()->parse($attributes, (int) $this->shopId);
    }

    private function createQuantity(array $product, int $quantity): Quantity
    {
        $availableQuantity = $this->getAvailableQuantity($product);

        return Quantity::integer(
            $quantity,
            $availableQuantity,
            $availableQuantity
        );
    }

    private function getAvailableQuantity(array $product): int
    {
        if (!isset($product['allow_oosp'])) {
            $outOfStock = \StockAvailable::outOfStock($product['id_product']);
            $product['allow_oosp'] = \Product::isAvailableWhenOutOfStock($outOfStock);
        }

        if ($product['allow_oosp']) {
            $availableQuantity = isset($product['quantity_available']) ? (int) $product['quantity_available'] : 0;

            return max(9999, $availableQuantity);
        }

        $availableQuantity = isset($product['quantity_available'])
            ? (int) $product['quantity_available']
            : \StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute']);

        return max(0, $availableQuantity);
    }

    /**
     * @param Product[] $products
     */
    private function createSummary(array $products): Summary
    {
        $basePrice = $this->getBasePrice($products);
        $finalPrice = $this->getFinalPrice();
        $promoPrice = $this->getPromoPrice();

        if ($promoPrice->getNet() === $finalPrice->getNet()) {
            // avoid inconsistent rounding by \Cart::getOrderTotal()
            $promoPrice = clone $finalPrice;
        }

        return new Summary(
            $basePrice,
            Currency::Pln(),
            $this->getPaymentOptions(),
            $finalPrice,
            $promoPrice,
            $this->expirationDate,
            $this->additionalInformation,
            $this->notice
        );
    }

    /**
     * @todo this actually makes no sense since products' prices are already rounded
     *
     * @param Product[] $products
     */
    private function getBasePrice(array $products): Price
    {
        $net = array_reduce($products, function ($sum, Product $product) {
            return $sum + $this->calculateRowTotal($product->getBasePrice()->getNet(), $product->getQuantity()->getQuantity());
        }, 0.);

        $gross = array_reduce($products, function ($sum, Product $product) {
            return $sum + $this->calculateRowTotal($product->getBasePrice()->getGross(), $product->getQuantity()->getQuantity());
        }, 0.);

        return PriceFactory::create($net, $gross);
    }

    private function getPromoPrice(): Price
    {
        if (isset($this->promoPrice)) {
            return $this->promoPrice;
        }

        $gross = (float) $this->cart->getOrderTotal(true, \Cart::ONLY_PRODUCTS, null, null, false, true);
        $net = (float) $this->cart->getOrderTotal(false, \Cart::ONLY_PRODUCTS, null, null, false, true);

        return $this->promoPrice = PriceFactory::create($net, $gross);
    }

    private function getFinalPrice(): Price
    {
        // between PS 1.7.4 and 1.7.6 \Cart::BOTH_WITHOUT_SHIPPING calculation type does not take cart rules into the account
        if (\Tools::version_compare(_PS_VERSION_, '1.7.4', '>=') && \Tools::version_compare(_PS_VERSION_, '1.7.6')) {
            return $this->getCartTotalWithoutShipping();
        }

        $gross = (float) $this->cart->getOrderTotal(true, \Cart::BOTH_WITHOUT_SHIPPING, null, null, false, true);
        $net = (float) $this->cart->getOrderTotal(false, \Cart::BOTH_WITHOUT_SHIPPING, null, null, false, true);

        return PriceFactory::create($net, $gross);
    }

    /**
     * @return Product[]
     */
    private function getRelatedProducts(array $cartProducts): array
    {
        if ([] === $relatedProducts = $this->prepareRelatedProducts($cartProducts)) {
            return [];
        }

        if (!$this->lowestPriceProvider instanceof BatchLowestPriceProviderInterface) {
            return array_map([$this, 'createRelatedProduct'], $relatedProducts);
        }

        $queries = $this->getLowestPriceQueries($relatedProducts, static function (array $product): bool {
            return 0. !== (float) $product['reduction'];
        });

        try {
            $this->lowestPriceProvider->preparePrices(...$queries);

            return array_map([$this, 'createRelatedProduct'], $relatedProducts);
        } finally {
            $this->lowestPriceProvider->reset();
        }
    }

    private function createRelatedProduct(array $product): Product
    {
        $model = new \Product($product['id_product'], false, $this->cart->id_lang);

        $quantity = !empty($product['id_product_attribute'])
            ? (int) (new \Combination((int) $product['id_product_attribute']))->minimal_quantity
            : (int) $model->minimal_quantity;

        if (isset($product['quantity'])) {
            $product['quantity_available'] = $product['quantity'];
        }

        return $this->createProduct(
            $model,
            $product,
            $this->createQuantity($product, $quantity),
            $this->getRelatedProductBasePrice($product, $quantity),
            $this->getRelatedProductPromoPrice($product, $quantity),
            true
        );
    }

    private function getRelatedProductBasePrice(array $product, int $quantity): Price
    {
        $gross = $product['price_without_reduction'] ?? $this->calculateProductPrice(
            (int) $product['id_product'],
            (int) $product['id_product_attribute'],
            true,
            false,
            $quantity
        );

        $net = $product['price_without_reduction_without_tax'] ?? $this->calculateProductPrice(
            (int) $product['id_product'],
            (int) $product['id_product_attribute'],
            false,
            false,
            $quantity
        );

        return PriceFactory::create((float) $net, (float) $gross);
    }

    private function getRelatedProductPromoPrice(array $product, int $quantity): ?Price
    {
        if (0. === (float) $product['reduction']) {
            return null;
        }

        $gross = $product['price'] ?? $this->calculateProductPrice(
            (int) $product['id_product'],
            (int) $product['id_product_attribute'],
            true,
            true,
            $quantity
        );

        $net = $product['price_tax_exc'] ?? $this->calculateProductPrice(
            (int) $product['id_product'],
            (int) $product['id_product_attribute'],
            false,
            true,
            $quantity
        );

        return PriceFactory::create((float) $net, (float) $gross);
    }

    /**
     * @return Consent[]
     */
    private function getConsents(): array
    {
        $configConsents = $this->consentsConfiguration->getConsents($shopId = (int) $this->shopId);

        if ([] === $configConsents) {
            return [];
        }

        $languageId = (int) $this->cart->id_lang;

        $cmsPages = \CMS::getCMSPages($languageId, null, true, $shopId);

        $cmsPagesById = [];
        foreach ($cmsPages as $page) {
            $cmsPagesById[$page['id_cms']] = $page;
        }

        $getLinkUrl = function (DTO\ConsentLink $link) use (&$cmsPagesById, $languageId, $shopId): ?string {
            if (!isset($cmsPagesById[$cmsId = $link->getCmsPageId()])) {
                return null;
            }

            $page = &$cmsPagesById[$cmsId];

            return $page['url'] ?? $page['url'] = $this->contextManager->getContext()->link->getCMSLink(
                $cmsId,
                $page['link_rewrite'],
                null,
                $languageId,
                $shopId
            );
        };

        $consents = [];

        foreach ($configConsents as $consent) {
            if (null === $mainUrl = $getLinkUrl($consent->getLink())) {
                continue;
            }

            $additionalLinks = [];
            foreach ($consent->getAdditionalLinks() as $link) {
                if (null === $additionalUrl = $getLinkUrl($link)) {
                    continue;
                }

                $additionalLinks[] = new ConsentLink(
                    $link->getId(),
                    $additionalUrl,
                    $link->getLabel($languageId)
                );
            }

            $consents[] = new Consent(
                $consent->getId(),
                $mainUrl,
                $this->getConsentDescription($consent, $languageId),
                $consent->getVersion(),
                $consent->getRequirementType() ?? ConsentRequirementType::Optional(),
                $consent->getLinkLabel($languageId),
                $additionalLinks
            );
        }

        return $consents;
    }

    private function getConsentDescription(DTO\Consent $consent, int $languageId): string
    {
        if ($description = $consent->getDescription($languageId)) {
            return $description;
        }

        $defaultLanguageId = (int) $this->getConfiguration('PS_LANG_DEFAULT');

        return $consent->getDescription($defaultLanguageId);
    }

    /**
     * @return PaymentType[]
     */
    private function getPaymentOptions(): array
    {
        /** @var \InPostIzi $module */
        $module = \Module::getInstanceByName('inpostizi');
        $configuration = $module->get(OrdersConfigurationInterface::class);

        return array_values($configuration->getAvailablePaymentOptions((int) $this->shopId));
    }

    private function calculateProductPrice(int $productId, ?int $combinationId = null, bool $withTax = true, bool $withReduction = true, int $quantity = 1, ?int $customizationId = null, ?int $shopId = null): ?float
    {
        if (null === $shopId || (int) $this->contextManager->getContext()->shop->id === $shopId) {
            $context = null;
        } else {
            $context = $this->contextManager->getContext()->cloneContext();
            $context->shop = new \Shop($shopId);
        }

        return \Product::getPriceStatic(
            $productId,
            $withTax,
            $combinationId,
            6,
            null,
            false,
            $withReduction,
            $quantity,
            false,
            $this->cart->id_customer,
            $this->cart->id,
            null,
            $specificPrice,
            true,
            true,
            $context,
            true,
            $customizationId
        );
    }

    private function calculateRowTotal(float $price, int $quantity): float
    {
        switch ($this->getConfiguration('PS_ROUND_TYPE')) {
            case \Order::ROUND_TOTAL:
                return (float) $price * $quantity;
            case \Order::ROUND_LINE:
                return (float) \Tools::ps_round($price * $quantity, $this->getPriceComputingPrecision());
            case \Order::ROUND_ITEM:
            default:
                return (float) \Tools::ps_round($price, $this->getPriceComputingPrecision()) * $quantity;
        }
    }

    private function getPriceComputingPrecision(): int
    {
        $context = $this->contextManager->getContext();

        if (is_callable([$context, 'getComputingPrecision'])) {
            return $context->getComputingPrecision();
        }

        if (defined('_PS_PRICE_COMPUTE_PRECISION_')) {
            return (int) _PS_PRICE_COMPUTE_PRECISION_;
        }

        return 2;
    }

    /**
     * @return false|string
     */
    private function getConfiguration(string $key)
    {
        return \Configuration::get($key, null, null, $this->shopId);
    }

    private function prepareRelatedProducts(array $cartProducts): array
    {
        if ([] === $cartProducts) {
            return [];
        }

        $limit = $this->getRelatedProductsLimit();

        if (null !== $limit && 0 >= $limit) {
            return [];
        }

        $cartProductsById = $relatedProductsById = [];

        foreach ($cartProducts as $cartProduct) {
            $cartProductsById[$cartProduct['id_product']] = $cartProduct;
        }

        foreach ($cartProductsById as $productId => $cartProduct) {
            $product = new \Product($productId, false, $this->cart->id_lang, $cartProduct['id_shop']);

            if (false === $accessories = $product->getAccessories($this->cart->id_lang)) {
                continue;
            }

            foreach ($accessories as $accessory) {
                $accessoryId = $accessory['id_product'];
                if (isset($relatedProductsById[$accessoryId]) || isset($cartProductsById[$accessoryId])) {
                    continue;
                }

                if (!$accessory['available_for_order']) {
                    continue;
                }

                if ($accessory['customizable'] > 1) {
                    continue; // product requires customization
                }

                if (!$accessory['allow_oosp'] && $accessory['quantity'] < $accessory['minimal_quantity']) {
                    continue;
                }

                $relatedProductsById[$accessoryId] = $accessory;

                if ($accessory['is_virtual']) {
                    $this->hasDigitalDelivery = true;
                }

                if (null !== $limit && 0 === --$limit) {
                    return array_values($relatedProductsById);
                }
            }
        }

        return array_values($relatedProductsById);
    }

    private function getRelatedProductsLimit(): ?int
    {
        $config = $this->getConfiguration('INPOST_PAY_related_count');

        return false === $config || '' === $config ? null : (int) $config;
    }

    private function getCartTotalWithoutShipping(): Price
    {
        $calculator = $this->getCartCalculator();

        $calculator->calculateRows();
        $calculator->calculateCartRules();

        $amount = $calculator->getRowTotal();

        return PriceFactory::create(
            $amount->getTaxExcluded(),
            $amount->getTaxIncluded()
        );
    }

    private function getCartCalculator(): Calculator
    {
        return (\Closure::bind(function (): Calculator {
            $products = $this->getProducts();
            $cartRules = $this->getTotalCalculationCartRules(self::BOTH_WITHOUT_SHIPPING, false);

            /** @var array{obj: \CartRule} $cartRule */
            foreach ($cartRules as $cartRule) {
                $cartRule['obj']->free_shipping = false;
            }

            return $this->newCalculator($products, $cartRules, null);
        }, $this->cart, \CartCore::class))();
    }

    /**
     * @return DeliveryOption[]
     */
    private function getAvailableDeliveryOptions(\Cart $cart): array
    {
        if (null === $this->availableDeliveryOptions) {
            $this->availableDeliveryOptions = array_values($this->deliveryFactory->getAvailableDeliveryOptions($cart, $this->shopId));

            foreach ($this->availableDeliveryOptions as $deliveryOption) {
                if (DeliveryType::Digital() === $deliveryOption->getType()) {
                    $this->hasDigitalDelivery = true;

                    break;
                }
            }
        }

        return $this->availableDeliveryOptions;
    }

    /**
     * @return DeliveryRelatedProducts[]|null
     */
    private function getDeliveryRelatedProducts(\Product $product, Price $price, Quantity $quantity): ?array
    {
        $productDeliveryDetails = [];

        foreach (DeliveryType::cases() as $deliveryType) {
            if (!$this->hasDigitalDelivery && DeliveryType::Digital() === $deliveryType) {
                continue;
            }

            $productDeliveryDetails[] = $this->createRelatedProductDeliveryDetails($deliveryType, $product, $price, $quantity);
        }

        return $productDeliveryDetails;
    }

    private function createRelatedProductDeliveryDetails(DeliveryType $deliveryType, \Product $product, Price $price, Quantity $quantity): DeliveryRelatedProducts
    {
        if (DeliveryType::Digital() === $deliveryType) {
            return new DeliveryRelatedProducts($deliveryType, (bool) $product->is_virtual, true);
        }

        if ($product->is_virtual) {
            $freeDelivery = $this->willExceedFreeDeliveryThreshold($deliveryType, $price, $quantity);

            return new DeliveryRelatedProducts($deliveryType, false, $freeDelivery);
        }

        $freeDeliveryAmount = $this->getFreeDeliveryAmount($deliveryType);

        if (!isset($this->cartSummary)) {
            $this->cartSummary = new Summary($this->getPromoPrice(), Currency::Pln(), []);
        }

        return $this->productDeliveryFactory->createForRelatedProduct($deliveryType, $this->cartSummary, $this->cart, $product, $price, $quantity, $freeDeliveryAmount, $this->shopId);
    }

    /**
     * @return DeliveryProduct[]|null
     */
    private function getDeliveryProduct(\Product $product, Price $price, Quantity $quantity, float $weight, array $productData): ?array
    {
        $hasUnavailable = false;
        $productDeliveryDetails = [];

        foreach (DeliveryType::cases() as $deliveryType) {
            if (!$this->hasDigitalDelivery && DeliveryType::Digital() === $deliveryType) {
                continue;
            }

            if ($this->isProductRestricted($productData, $deliveryType)) {
                $productDelivery = new DeliveryProduct($deliveryType, false);
            } else {
                $productDelivery = $this->createCartProductDeliveryDetails($deliveryType, $product, $price, $quantity, $weight);
            }

            $productDeliveryDetails[] = $productDelivery;

            if (!$productDelivery->isDeliveryAvailable()) {
                $hasUnavailable = true;
            }
        }

        if (!$hasUnavailable) {
            return null;
        }

        return $productDeliveryDetails;
    }

    private function isProductRestricted(array $product, DeliveryType $deliveryType): bool
    {
        if (null === $this->validator) {
            return false;
        }

        $violations = $this->validator->validate($product, new Unrestricted([
            'shopId' => (int) $product['id_shop'],
            'deliveryType' => $deliveryType,
        ]));

        return 0 !== $violations->count();
    }

    private function getDeliveryOption(DeliveryType $deliveryType): ?DeliveryOption
    {
        foreach ($this->getAvailableDeliveryOptions($this->cart) as $deliveryOption) {
            if ($deliveryType === $deliveryOption->getType()) {
                return $deliveryOption;
            }
        }

        return null;
    }

    private function createCartProductDeliveryDetails(DeliveryType $deliveryType, \Product $product, Price $price, Quantity $quantity, float $weight): DeliveryProduct
    {
        if (DeliveryType::Digital() === $deliveryType) {
            return new DeliveryProduct($deliveryType, (bool) $product->is_virtual);
        }

        if ($product->is_virtual) {
            return new DeliveryProduct($deliveryType, false);
        }

        if (null !== $this->getDeliveryOption($deliveryType)) {
            // the delivery option would not be available for the cart if it was not available for the product
            return new DeliveryProduct($deliveryType, true);
        }

        return $this->productDeliveryFactory->createForCartProduct($deliveryType, $this->cart, $product, $price, $weight, $quantity, $this->shopId);
    }

    private function getFreeDeliveryAmount(DeliveryType $deliveryType): ?float
    {
        if (null === $deliveryOption = $this->getDeliveryOption($deliveryType)) {
            return null;
        }

        if (null === $amount = $deliveryOption->getFreeDeliveryMinimumGrossPrice()) {
            return null;
        }

        return $amount->getPriceAmount();
    }

    private function getLowestPrice(int $productId, ?int $combinationId = null, ?int $shopId = null): ?Price
    {
        $query = $this->createLowestPriceQuery($productId, $combinationId, $shopId);

        return $this->lowestPriceProvider->getPrice($query);
    }

    private function getLowestPriceQueries(array $products, \Closure $reductionChecker): array
    {
        $queries = [];

        foreach ($products as $product) {
            if (!$reductionChecker($product)) {
                continue;
            }

            $productId = (int) $product['id_product'];
            $combinationId = 0 < $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null;

            $idShop = array_key_exists('id_shop', $product) ? (int) $product['id_shop'] : null;

            $queries[] = $this->createLowestPriceQuery($productId, $combinationId, $idShop);
        }

        return $queries;
    }

    private function createLowestPriceQuery(int $productId, ?int $combinationId = null, ?int $shopId = null): LowestPriceQuery
    {
        $context = $this->contextManager->getContext();

        $customer = $context->customer;
        $customerGroupId = 0 < (int) $customer->id
            ? (int) $customer->id_default_group
            : (int) $this->getConfiguration('PS_UNIDENTIFIED_GROUP');

        return new LowestPriceQuery(
            $productId,
            $shopId ?? (int) $this->shopId,
            (int) $this->cart->id_currency,
            (int) $context->country->id,
            $customerGroupId,
            $combinationId
        );
    }

    private function getAttributeListParser(): AttributeListParser
    {
        return $this->attributeListParser ?? $this->attributeListParser = new AttributeListParser(
            new PrestaShopConfiguration(new Configuration()),
            $this->contextManager->getContext(),
            _PS_VERSION_
        );
    }

    private function getImageProvider(): ImageUrlsProviderInterface
    {
        if (isset($this->imageProvider)) {
            return $this->imageProvider;
        }

        try {
            /** @var \InPostIzi $module */
            $module = \Module::getInstanceByName('inpostizi');

            return $this->imageProvider = $module->get(ImageUrlsProviderInterface::class);
        } catch (ServiceNotFoundException $e) {
            return $this->imageProvider = ImageUrlsProvider::create(
                $this->imageRetriever,
                $this->contextManager->getContext(),
                $this->productConfiguration
            );
        }
    }

    private function willExceedFreeDeliveryThreshold(DeliveryType $deliveryType, Price $productPrice, Quantity $quantity): bool
    {
        if (null === $freeDeliveryAmount = $this->getFreeDeliveryAmount($deliveryType)) {
            return false;
        }

        $basketPrice = $this->getPromoPrice();
        $newBasketPrice = $basketPrice->add($productPrice->multiply($quantity->getQuantity()));

        return $freeDeliveryAmount <= $newBasketPrice->getGross();
    }
}
