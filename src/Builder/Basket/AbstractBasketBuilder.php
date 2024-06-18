<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Builder\PriceFactory;
use izi\prestashop\Common\Basket\Consent;
use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Common\Basket\Notice;
use izi\prestashop\Common\Basket\Product;
use izi\prestashop\Common\Basket\Quantity;
use izi\prestashop\Common\Basket\Summary;
use izi\prestashop\Common\Currency;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Common\Price;
use izi\prestashop\Common\Product\ProductAttribute;
use izi\prestashop\Common\Product\ProductVariant;
use izi\prestashop\Common\PromoCode;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\ConsentsConfigurationInterface;
use izi\prestashop\Configuration\DTO;
use izi\prestashop\Configuration\OrdersConfiguration;
use izi\prestashop\ContextManager;
use PrestaShop\PrestaShop\Core\Cart\Calculator;

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
     * @var DeliveryFactory
     */
    private $deliveryFactory;

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

    public function __construct(\Cart $cart, ContextManager $contextManager, ConsentsConfigurationInterface $consentsConfiguration, DeliveryFactory $deliveryFactory)
    {
        $this->cart = $cart;
        $this->contextManager = $contextManager;
        $this->consentsConfiguration = $consentsConfiguration;
        $this->deliveryFactory = $deliveryFactory;
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
            $products = array_map([$this, 'createCartProduct'], $cartProducts);

            return $this->doBuild(
                $this->createSummary($products),
                $this->deliveryFactory->getAvailableDeliveryOptions($this->cart),
                $products,
                $this->getConsents(),
                $this->getPromoCodes(),
                $this->getRelatedProducts($cartProducts)
            );
        } finally {
            $this->contextManager->restoreContext();
        }
    }

    abstract protected function doBuild(Summary $summary, array $delivery, array $products, array $consents, array $promoCodes, array $relatedProducts);

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

    private function getCartProductPromoPrice(array $product): Price
    {
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

    private function createProduct(\Product $model, array $product, Quantity $quantity, Price $basePrice, Price $promoPrice): Product
    {
        $combinationId = array_key_exists('id_product_attribute', $product) ? (int) $product['id_product_attribute'] : 0;
        $customizationId = array_key_exists('id_customization', $product) ? (int) $product['id_customization'] : 0;

        $category = $model->getDefaultCategory();
        $description = $this->formatDescription((string) $model->description) ?: $this->formatDescription((string) $model->description_short);
        $link = \Context::getContext()->link->getProductLink($model, null, null, null, $this->cart->id_lang, null, $combinationId);

        return new Product(
            sprintf('%d.%d.%d', $model->id, $combinationId, $customizationId),
            $product['name'],
            $basePrice,
            $quantity,
            is_array($category) ? (string) current($category) : (string) $category,
            $product['ean13'] ?? $model->ean13,
            $description,
            $link,
            $this->getImageUrl($product, $model, $combinationId),
            $promoPrice,
            null,
            $this->getProductAttributes($product),
            $this->getProductVariants($product)
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

    private function getImageUrl(array $product, \Product $model, int $combinationId): string
    {
        $imageId = $product['id_image'] ?? $this->getDefaultImageId($model->id, $combinationId);

        if (null === $imageId) {
            return '';
        }

        $linkRewrite = $model->link_rewrite ?? \Tools::str2url($model->name);

        return \Context::getContext()->link->getImageLink($linkRewrite, $imageId, 'small_default');
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
            [$group, $name] = explode(':', $attribute, 2);

            $attributes[] = [
                'group' => trim($group),
                'name' => trim($name),
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
     * @return PromoCode[]
     */
    private function getPromoCodes(): array
    {
        $cartRules = $this->cart->getCartRules(\CartRule::FILTER_ACTION_ALL, true, true);

        return array_map([$this, 'createPromoCode'], $cartRules);
    }

    private function createPromoCode(array $cartRule): PromoCode
    {
        $code = $cartRule['code'] ?: $cartRule['name'];

        return new PromoCode($cartRule['name'], $code);
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

        return array_map([$this, 'createRelatedProduct'], $relatedProducts);
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
            $this->getRelatedProductPromoPrice($product, $quantity)
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

    private function getRelatedProductPromoPrice(array $product, int $quantity): Price
    {
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
        $configConsents = $this->consentsConfiguration->getConsents((int) $this->cart->id_shop);

        if ([] === $configConsents) {
            return [];
        }

        $context = \Context::getContext();
        $languageId = (int) $this->cart->id_lang;

        $cmsPages = \CMS::getCMSPages($languageId, null, true, $this->cart->id_shop);

        $cmsPagesById = [];
        foreach ($cmsPages as $page) {
            $cmsPagesById[$page['id_cms']] = $page;
        }

        $consents = [];

        foreach ($configConsents as $consent) {
            if (!isset($cmsPagesById[$cmsId = $consent->getCmsPageId()])) {
                continue;
            }

            $page = &$cmsPagesById[$cmsId];
            if (!isset($page['url'])) {
                $page['url'] = $context->link->getCMSLink($cmsId, $page['link_rewrite'], null, $languageId, $this->cart->id_shop);
            }

            $consents[] = new Consent(
                $consent->getId(),
                $page['url'],
                $this->getConsentDescription($consent, $languageId),
                $consent->getVersion(),
                $consent->getRequirementType() ?? ConsentRequirementType::Optional()
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

    private function calculateProductPrice(
        int $productId,
        ?int $combinationId = null,
        bool $withTax = true,
        bool $withReduction = true,
        int $quantity = 1,
        ?int $customizationId = null
    ): ?float {
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
        $context = \Context::getContext();

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

    private function getDefaultImageId(int $productId, int $combinationId): ?int
    {
        if ($imageId = $this->getDefaultCombinationImageId($combinationId)) {
            return $imageId;
        }

        $cover = \Product::getCover($productId);

        return isset($cover['id_image']) ? (int) $cover['id_image'] : null;
    }

    private function getDefaultCombinationImageId(int $combinationId): ?int
    {
        if (0 >= $combinationId) {
            return null;
        }

        $query = (new \DbQuery())
            ->select('pai.id_image')
            ->from('product_attribute_image', 'pai')
            ->innerJoin('image', 'i', 'i.id_image = pai.id_image')
            ->join(\Shop::addSqlAssociation('image', 'i'))
            ->where('pai.id_product_attribute = ' . $combinationId)
            ->orderBy('image_shop.cover DESC')
            ->orderBy('i.position ASC');

        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        return false === $result ? null : (int) $result;
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
}
