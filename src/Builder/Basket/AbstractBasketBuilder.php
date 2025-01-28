<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Builder\PriceFactory;
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
use izi\prestashop\Common\Product\ProductImage;
use izi\prestashop\Common\Product\ProductVariant;
use izi\prestashop\Common\PromoCode;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\ConsentsConfigurationInterface;
use izi\prestashop\Configuration\DTO;
use izi\prestashop\Configuration\OrdersConfiguration;
use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\ContextManager;
use izi\prestashop\Database\Connection;
use izi\prestashop\Product\Price\BatchLowestPriceProviderInterface;
use izi\prestashop\Product\Price\LowestPriceProviderInterface;
use izi\prestashop\Product\Price\LowestPriceQuery;
use izi\prestashop\Product\Price\NullLowestPriceProvider;
use izi\prestashop\PromoCode\CartRulePromoCodeProvider;
use izi\prestashop\PromoCode\PromoCodeProviderInterface;
use izi\prestashop\Repository\CartRuleRepository;
use izi\prestashop\Repository\CartRuleRepositoryInterface;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\Cart\Calculator;

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
     * @var ImageRetriever
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
     * @var DeliveryProduct[]|null
     */
    private $availableDeliveryOptions;

    /**
     * @var Summary
     */
    private $cartSummary;

    public function __construct(
        \Cart $cart,
        ContextManager $contextManager,
        ConsentsConfigurationInterface $consentsConfiguration,
        ProductConfigurationInterface $productConfiguration,
        DeliveryFactory $deliveryFactory,
        ProductDeliveryFactory $deliveryRelatedProductFactory,
        ?ImageRetriever $imageRetriever = null,
        ?LowestPriceProviderInterface $lowestPriceProvider = null,
        ?PromoCodeProviderInterface $promoCodeProvider = null
    ) {
        $this->cart = $cart;
        $this->contextManager = $contextManager;
        $this->consentsConfiguration = $consentsConfiguration;
        $this->deliveryFactory = $deliveryFactory;
        $this->productConfiguration = $productConfiguration;
        $this->productDeliveryFactory = $deliveryRelatedProductFactory;
        $this->imageRetriever = $imageRetriever ?? new ImageRetriever($contextManager->getContext()->link);
        $this->lowestPriceProvider = $lowestPriceProvider ?? new NullLowestPriceProvider();
        $this->promoCodeProvider = $promoCodeProvider ?? CartRulePromoCodeProvider::create();
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
            $this->contextManager->changeContext($this->cart);

            $cartProducts = $this->cart->getProducts(false, false, null, true, true);
            $products = $this->createCartProducts($cartProducts);
            $this->cartSummary = $this->createSummary($products);

            return $this->doBuild(
                $this->cartSummary,
                $this->getAvailableDeliveryOptions($this->cart),
                $products,
                $this->getConsents(),
                $this->promoCodeProvider->getPromoCodes($this->cart),
                $this->getRelatedProducts($cartProducts)
            );
        } finally {
            $this->contextManager->restoreContext();
        }
    }

    abstract protected function doBuild(Summary $summary, array $delivery, array $products, array $consents, array $promoCodes, array $relatedProducts);

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
        $model = new \Product($product['id_product'], false, $this->cart->id_lang);

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
            (int) $product['id_customization']
        );

        $net = $product['price_without_reduction_without_tax'] ?? $this->calculateProductPrice(
            (int) $product['id_product'],
            (int) $product['id_product_attribute'],
            false,
            false,
            (int) $product['cart_quantity'],
            (int) $product['id_customization']
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
            (int) $product['id_customization']
        );

        $net = $product['price_with_reduction_without_tax'] ?? $this->calculateProductPrice(
            (int) $product['id_product'],
            (int) $product['id_product_attribute'],
            false,
            true,
            (int) $product['cart_quantity'],
            (int) $product['id_customization']
        );

        return PriceFactory::create((float) $net, (float) $gross);
    }

    private function createProduct(\Product $model, array $product, Quantity $quantity, Price $basePrice, ?Price $promoPrice, bool $related = false): Product
    {
        $combinationId = array_key_exists('id_product_attribute', $product) ? (int) $product['id_product_attribute'] : null;
        $customizationId = array_key_exists('id_customization', $product) ? (int) $product['id_customization'] : 0;

        $category = $model->getDefaultCategory();
        $description = $this->formatDescription((string) $model->description) ?: $this->formatDescription((string) $model->description_short);
        $link = $this->contextManager->getContext()->link->getProductLink($model, null, null, null, $this->cart->id_lang, null, $combinationId);

        $images = $this->imageRetriever->getProductImages($product, $this->contextManager->getContext()->language);
        $imageUrl = $this->getCoverImageUrl($images);

        return new Product(
            sprintf('%d.%d.%d', $model->id, (int) $combinationId, $customizationId),
            $product['name'],
            $basePrice,
            $quantity,
            is_array($category) ? (string) current($category) : (string) $category,
            $product['ean13'] ?? $model->ean13,
            $description,
            $link,
            $imageUrl,
            $promoPrice,
            null === $promoPrice ? null : $this->getLowestPrice((int) $model->id, $combinationId),
            $this->getProductAttributes($product),
            $this->getProductVariants($product),
            $this->getProductImages($images),
            $related ? null : $this->getDeliveryProduct($model, $promoPrice ?? $basePrice, $quantity, (float) $product['weight']),
            $related ? $this->getDeliveryRelatedProducts($model, $promoPrice ?? $basePrice, $quantity) : null
        );
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

    /**
     * @todo unused for now
     *
     * @return ProductVariant[]
     */
    private function getProductVariants(array $product): array
    {
        return [];
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

        $separator = $this->getConfiguration('PS_ATTRIBUTE_ANCHOR_SEPARATOR');
        $pattern = sprintf('/(?>(?P<attribute>[^:]+:[^:]+)%1$s(?!%1$s([^:%1$s])+:))/', $separator);

        if (!preg_match_all($pattern, $attributes . $separator, $matches)) {
            return [];
        }

        $attributes = [];

        foreach ($matches['attribute'] as $attribute) {
            [$group, $name] = array_map('trim', explode(':', $attribute, 2));

            if ('' === $group || '' === $name) {
                continue;
            }

            $attributes[] = [
                'group' => $group,
                'name' => $name,
            ];
        }

        return $attributes;
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

        if (
            [] !== $this->cart->getCartRules(\CartRule::FILTER_ACTION_REDUCTION, false, true)
            || [] !== $this->cart->getCartRules(\CartRule::FILTER_ACTION_GIFT, false, true)
        ) {
            $promoPrice = $this->getPromoPrice();
        } else {
            $promoPrice = clone $finalPrice; // avoid inconsistent rounding by \Cart::getOrderTotal()
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
        $gross = (float) $this->cart->getOrderTotal(true, \Cart::ONLY_PRODUCTS, null, null, false, true);
        $net = (float) $this->cart->getOrderTotal(false, \Cart::ONLY_PRODUCTS, null, null, false, true);

        return PriceFactory::create($net, $gross);
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
            ? (int) (new \Combination($product['id_product_attribute']))->minimal_quantity
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
        $configConsents = $this->consentsConfiguration->getConsents($shopId = (int) $this->cart->id_shop);

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
        $configuration = new OrdersConfiguration(new Configuration());

        return OrdersConfiguration::normalizeAvailablePaymentOptions($configuration, (int) $this->cart->id_shop);
    }

    private function calculateProductPrice(int $productId, ?int $combinationId = null, bool $withTax = true, bool $withReduction = true, int $quantity = 1, ?int $customizationId = null): ?float
    {
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
            null,
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
        return \Configuration::get($key, null, null, $this->cart->id_shop);
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
            $product = new \Product($productId, false, $this->cart->id_lang);

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

    private function getImageTypeNameByImageTypeId(int $idImageType, string $defaultValue): string
    {
        $imageType = $idImageType ? new \ImageType($idImageType) : null;

        return $imageType && \Validate::isLoadedObject($imageType) ? $imageType->name : $defaultValue;
    }

    private function getCoverImageUrl(array $images): ?string
    {
        if (null === $image = $this->getCoverImage($images)) {
            return null;
        }

        $idImageType = $this->productConfiguration->getNormalImageTypeId((int) $this->cart->id_shop);

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

        $idImageTypeSmall = $this->productConfiguration->getSmallImageTypeId((int) $this->cart->id_shop);
        $idImageTypeLarge = $this->productConfiguration->getLargeImageTypeId((int) $this->cart->id_shop);
        $smallFormatName = $this->getImageTypeNameByImageTypeId($idImageTypeSmall, 'home_default');
        $largeFormatName = $this->getImageTypeNameByImageTypeId($idImageTypeLarge, 'medium_default');

        return array_map(static function (array $image) use ($smallFormatName, $largeFormatName): ProductImage {
            $smallSize = $image['bySize'][$smallFormatName] ?? $image['medium'];
            $normalSize = $image['bySize'][$largeFormatName] ?? $image['large'];

            return new ProductImage($smallSize['url'], $normalSize['url']);
        }, $images);
    }

    /**
     * @return DeliveryOption[]
     */
    private function getAvailableDeliveryOptions(\Cart $cart): array
    {
        if (null === $this->availableDeliveryOptions) {
            $this->availableDeliveryOptions = $this->deliveryFactory->getAvailableDeliveryOptions($cart);
        }

        return $this->availableDeliveryOptions;
    }

    /**
     * @return DeliveryRelatedProducts[]|null
     */
    private function getDeliveryRelatedProducts(\Product $productModel, Price $price, Quantity $quantity): ?array
    {
        $productDeliveryDetails = [];

        if (null === $this->cartSummary) {
            return null;
        }

        foreach (DeliveryType::cases() as $deliveryType) {
            $freeDeliveryAmount = $this->getFreeDeliveryAmount($deliveryType);
            $productDelivery = $this->productDeliveryFactory->createForRelatedProduct($deliveryType, $this->cartSummary, $this->cart, $productModel, $price, $quantity, $freeDeliveryAmount);
            $productDeliveryDetails[] = $productDelivery;
        }

        return $productDeliveryDetails;
    }

    /**
     * @return DeliveryProduct[]|null
     */
    private function getDeliveryProduct(\Product $product, Price $price, Quantity $quantity, float $weight): ?array
    {
        $hasUnavailable = false;
        $productDeliveryDetails = [];

        foreach (DeliveryType::cases() as $deliveryType) {
            $productDelivery = $this->createCartProductDeliveryDetails($deliveryType, $product, $price, $quantity, $weight);
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

    private function createCartProductDeliveryDetails(DeliveryType $deliveryType, \Product $product, Price $price, Quantity $quantity, float $weight): DeliveryProduct
    {
        foreach ($this->getAvailableDeliveryOptions($this->cart) as $deliveryOption) {
            if ($deliveryType === $deliveryOption->getType()) {
                return new DeliveryProduct($deliveryType, true);
            }
        }

        return $this->productDeliveryFactory->createForCartProduct($deliveryType, $this->cart, $product, $price, $weight, $quantity);
    }

    private function getFreeDeliveryAmount(DeliveryType $deliveryType): ?float
    {
        foreach ($this->getAvailableDeliveryOptions($this->cart) as $deliveryOption) {
            if ($deliveryType === $deliveryOption->getType()) {
                if (null === $amount = $deliveryOption->getFreeDeliveryMinimumGrossPrice()) {
                    return null;
                }

                return $amount->getPriceAmount();
            }
        }

        return null;
    }

    private function getLowestPrice(int $productId, ?int $combinationId = null): ?Price
    {
        $query = $this->createLowestPriceQuery($productId, $combinationId);

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

            $queries[] = $this->createLowestPriceQuery($productId, $combinationId);
        }

        return $queries;
    }

    private function createLowestPriceQuery(int $productId, ?int $combinationId = null): LowestPriceQuery
    {
        $context = $this->contextManager->getContext();

        $customer = $context->customer;
        $customerGroupId = 0 < (int) $customer->id
            ? (int) $customer->id_default_group
            : (int) $this->getConfiguration('PS_UNIDENTIFIED_GROUP');

        return new LowestPriceQuery(
            $productId,
            (int) $this->cart->id_shop,
            (int) $this->cart->id_currency,
            (int) $context->country->id,
            $customerGroupId,
            $combinationId
        );
    }
}
