<?php

namespace izi\prestashop;

use izi\BasketIdentification;
use izi\item\Basket;
use izi\item\Quantity;
use izi\prestashop\traits\PriceFactoryTrait;
use izi\Storage;

class PrestashopBasket
{
    use PriceFactoryTrait;

    private $basketId;
    private $cart;

    public function __construct(string $basketId, \Cart $cart)
    {
        $this->basketId = $basketId;
        $this->cart = $cart;
    }

    public static function createForCart(\Cart $cart, string $basketId): Basket
    {
        return (new self($basketId, $cart))->mapBasket();
    }

    public static function createForCustomerContext(): Basket
    {
        $cart = \Context::getContext()->cart ?? new \Cart();
        $basketId = BasketIdentification::get();

        return self::createForCart($cart, $basketId);
    }

    public function mapBasket(): Basket
    {
        $basket = new Basket($this->basketId);

        $cart = $this->cart;
        $cartProducts = $cart->getProducts(false, false, null, true, true);

        $basket->products = $this->mapProducts($cartProducts);
        $basket->summary = $this->mapSummary($basket->products);

        $basket->promo_codes = $this->mapPromoCodes();
        $basket->delivery = (new PrestaDeliveryPrice())->mapDelivery($cart);
        $basket->consents = $this->mapConsents();

        $basket->related_products = $this->mapRelatedProducts($cartProducts);
        $basket->browser_id = $this->getBrowserId();

        return $basket;
    }

    /**
     * @return \izi\item\BasketProduct[]
     */
    public function mapProducts(array $products): array
    {
        $result = [];

        foreach ($products as $product) {
            try {
                $result[] = $this->mapCartProduct($product);
            } catch (\Exception $e) {
                Logger::log($e->getMessage());
            }
        }

        return $result;
    }

    public function mapCartProduct(array $productData): \izi\item\BasketProduct
    {
        $prestashopProduct = new \Product($productData['id_product'], false, $this->cart->id_lang);

        $product = $this->mapProductData($prestashopProduct, $productData);

        $product->quantity = $this->readQuantity($productData, $prestashopProduct);
        $product->base_price = $this->readCartProductBasePrice($productData);
        $product->promo_price = $this->readCartProductPromoPrice($productData);

        return $product;
    }

    public function readCartProductBasePrice(array $productData): \izi\item\Price
    {
        $gross = $productData['price_without_reduction'] ?? $this->calculateProductPrice(
            $productData['id_product'],
            $productData['id_product_attribute'],
            true,
            false,
            $productData['cart_quantity'],
            $productData['id_customization']
        );

        $net = $productData['price_without_reduction_without_tax'] ?? $this->calculateProductPrice(
            $productData['id_product'],
            $productData['id_product_attribute'],
            false,
            false,
            $productData['cart_quantity'],
            $productData['id_customization']
        );

        return $this->createPrice($net, $gross);
    }

    public function readCartProductPromoPrice(array $productData): \izi\item\Price
    {
        $gross = $productData['price_with_reduction'] ?? $this->calculateProductPrice(
            $productData['id_product'],
            $productData['id_product_attribute'],
            true,
            true,
            $productData['cart_quantity'],
            $productData['id_customization']
        );

        $net = $productData['price_with_reduction_without_tax'] ?? $this->calculateProductPrice(
            $productData['id_product'],
            $productData['id_product_attribute'],
            false,
            true,
            $productData['cart_quantity'],
            $productData['id_customization']
        );

        return $this->createPrice($net, $gross);
    }

    public function mapProductData(\Product $prestashopProduct, array $productData): \izi\item\BasketProduct
    {
        $product = new \izi\item\BasketProduct();

        $combinationId = array_key_exists('id_product_attribute', $productData) ? (int) $productData['id_product_attribute'] : 0;
        $customizationId = array_key_exists('id_customization', $productData) ? (int) $productData['id_customization'] : 0;

        $product->product_id = $prestashopProduct->id . '.' . $combinationId . '.' . $customizationId;
        $product->product_category = $prestashopProduct->getDefaultCategory();
        $product->ean = $productData['ean13'] ?? $prestashopProduct->ean13;
        $product->product_name = $prestashopProduct->name;
        $product->product_description = \Tools::substr(trim(strip_tags($prestashopProduct->description)), 0, 1000);
        $product->product_link = \Context::getContext()->link->getProductLink($prestashopProduct, null, null, null, $this->cart->id_lang, null, $combinationId);

        $product->product_image = $this->readProductImage($prestashopProduct, $combinationId);

        $product->variants = $this->mapProductVariables($prestashopProduct);
        $product->product_attributes = [];

        return $product;
    }

    public function readProductImage(\Product $product, int $combinationId): string
    {
        if (null === $imageId = $this->getDefaultImageId($product->id, $combinationId)) {
            return '';
        }

        $linkRewrite = $product->link_rewrite ?? \Tools::str2url($product->name);

        return \Context::getContext()->link->getImageLink($linkRewrite, $imageId, 'small_default');
    }

    /**
     * @return \izi\item\Variant[]
     */
    public function mapProductVariables(\Product $prestashopProduct): array
    {
        $result = [];

        $map = [];
        foreach ($prestashopProduct->getAttributeCombinations($this->cart->id_lang) as $attribute) {
            if (!isset($map[$attribute['id_attribute_group']]) || !is_array($map[$attribute['id_attribute_group']])) {
                $map[$attribute['id_attribute_group']] = [];
            }

            $map[$attribute['id_attribute_group']][] = $attribute;
        }

        foreach ($map as $item) {
            $result[] = $this->mapProductVariable($item);
        }

        return $result;
    }

    public function mapProductVariable($item): \izi\item\Variant
    {
        $variant = new \izi\item\Variant();

        $variant->variant_id = $item[0]['id_attribute_group'];
        $variant->variant_name = $item[0]['group_name'];

        $variantValues = [];
        foreach ($item as $attribute) {
            $variantValues[] = $attribute['attribute_name'];
        }

        $variant->variant_values = implode(', ', $variantValues);

        $variant->variant_description = '';
        $variant->variant_type = '';

        return $variant;
    }

    /**
     * @return \izi\item\BasketQuantity
     */
    public function readQuantity(array $productData, \Product $prestashopProduct)
    {
        $quantity = $this->readStockQuantity($prestashopProduct, $productData);
        $quantity->quantity = (int) $productData['cart_quantity'];

        return $quantity;
    }

    /**
     * @return \izi\item\BasketQuantity
     */
    public function readStockQuantity(\Product $prestashopProduct, array $productData)
    {
        $quantity = new \izi\item\BasketQuantity();

        $quantity->quantity_type = Quantity::INTEGER;
        $quantity->quantity_unit = 'pcs';

        if (!isset($productData['allow_oosp'])) {
            $outOfStock = \StockAvailable::outOfStock($prestashopProduct->id);
            $productData['allow_oosp'] = \Product::isAvailableWhenOutOfStock($outOfStock);
        }

        if ($productData['allow_oosp']) {
            $availableQuantity = 9999;
        } else {
            $availableQuantity = isset($productData['quantity_available'])
                ? (int) $productData['quantity_available']
                : \StockAvailable::getQuantityAvailableByProduct($prestashopProduct->id, $productData['id_product_attribute']);

            if (0 > $availableQuantity) {
                $availableQuantity = 0;
            }
        }

        $quantity->available_quantity = $availableQuantity;
        $quantity->max_quantity = $availableQuantity;

        return $quantity;
    }

    /**
     * @return \izi\item\PromoCode[]
     */
    public function mapPromoCodes(): array
    {
        $result = [];

        foreach ($this->cart->getCartRules(\CartRule::FILTER_ACTION_ALL, true, true) as $rule) {
            $result[] = $this->mapPromoCode($rule);
        }

        return $result;
    }

    public function mapPromoCode(array $rule): \izi\item\PromoCode
    {
        $promoCode = new \izi\item\PromoCode();

        $promoCode->name = $rule['name'];
        $promoCode->promo_code_value = $rule['code'] ?: $rule['name'];

        return $promoCode;
    }

    /**
     * @param \izi\item\BasketProduct[]
     */
    public function mapSummary(array $products): \izi\item\Summary
    {
        $summary = new \izi\item\Summary();

        $summary->basket_base_price = $this->readSummaryBasketBasePrice($products);
        $summary->basket_final_price = $this->readSummaryBasketFinalPrice();

        if (
            [] !== $this->cart->getCartRules(\CartRule::FILTER_ACTION_REDUCTION, false, true) ||
            [] !== $this->cart->getCartRules(\CartRule::FILTER_ACTION_GIFT, false, true)
        ) {
            $summary->basket_promo_price = $this->readSummaryBasketPromoPrice();
        } else {
            $summary->basket_promo_price = clone $summary->basket_final_price; // avoid inconsistent rounding by \Cart::getOrderTotal()
        }

        $summary->currency = 'PLN';
        $summary->basket_expiration_date = $this->readBasketExpirationDate();
        $summary->basket_additional_information = '';
        $summary->payment_type = $this->readPaymentType();

        return $summary;
    }

    public function readBasketExpirationDate()
    {
        return date("Y-m-d\TH:i:s.000\Z", strtotime('+2 days'));
    }

    /**
     * @param \izi\item\BasketProduct[] $products
     */
    public function readSummaryBasketBasePrice(array $products): \izi\item\Price
    {
        $net = array_reduce($products, function ($sum, \izi\item\BasketProduct $product) {
            return $sum + $this->calculateRowTotal($product->base_price->net, $product->quantity->quantity);
        }, 0.);

        $gross = array_reduce($products, function ($sum, \izi\item\BasketProduct $product) {
            return $sum + $this->calculateRowTotal($product->base_price->gross, $product->quantity->quantity);
        }, 0.);

        return $this->createPrice($net, $gross);
    }

    public function readSummaryBasketPromoPrice(): \izi\item\Price
    {
        $gross = $this->cart->getOrderTotal(true, \Cart::ONLY_PRODUCTS, null, null, false, true);
        $net = $this->cart->getOrderTotal(false, \Cart::ONLY_PRODUCTS, null, null, false, true);

        return $this->createPrice($net, $gross);
    }

    public function readSummaryBasketFinalPrice(): \izi\item\Price
    {
        $gross = $this->cart->getOrderTotal(true, \Cart::BOTH_WITHOUT_SHIPPING, null, null, false, true);
        $net = $this->cart->getOrderTotal(false, \Cart::BOTH_WITHOUT_SHIPPING, null, null, false, true);

        return $this->createPrice($net, $gross);
    }

    /**
     * @return \izi\item\BasketProduct[]
     */
    public function mapRelatedProducts(array $cartProducts): array
    {
        $limit = $this->getRelatedProductsLimit();

        if (null !== $limit && 0 >= $limit) {
            return [];
        }

        $result = $cartProductsById = $relatedProductsById = [];

        foreach ($cartProducts as $cartProduct) {
            if (isset($cartProductsById[$cartProduct['id_product']])) {
                continue;
            }

            $cartProductsById[$cartProduct['id_product']] = $cartProduct;
            $prestashopProduct = new \Product($cartProduct['id_product'], false, $this->cart->id_lang);

            foreach ($prestashopProduct->getAccessories($this->cart->id_lang) as $accessory) {
                if ($accessory['customizable'] > 1) {
                    continue; // product requires customization
                }

                $relatedProductsById[$accessory['id_product']] = $accessory;
            }
        }

        $relatedProducts = array_diff_key($relatedProductsById, $cartProductsById);
        if (null !== $limit) {
            $relatedProducts = array_slice($relatedProducts, 0, $limit);
        }

        foreach ($relatedProducts as $relatedProduct) {
            $result[] = $this->mapRelatedProduct($relatedProduct);
        }

        return $result;
    }

    public function mapRelatedProduct(array $productData): \izi\item\BasketProduct
    {
        $prestashopProduct = new \Product($productData['id_product'], false, $this->cart->id_lang);

        $quantity = !empty($productData['id_product_attribute'])
            ? (new \Combination($productData['id_product_attribute']))->minimal_quantity
            : $prestashopProduct->minimal_quantity;

        $product = $this->mapProductData($prestashopProduct, $productData);
        $product->base_price = $this->readRelatedProductBasePrice($productData, $quantity);
        $product->promo_price = $this->readRelatedProductPromoPrice($productData, $quantity);

        if (isset($productData['quantity'])) {
            $productData['quantity_available'] = $productData['quantity'];
        }

        $product->quantity = $this->readStockQuantity($prestashopProduct, $productData);
        $product->quantity->quantity = $quantity;

        return $product;
    }

    public function readRelatedProductBasePrice(array $productData, int $quantity): \izi\item\Price
    {
        $gross = $productData['price_without_reduction'] ?? $this->calculateProductPrice(
            $productData['id_product'],
            $productData['id_product_attribute'],
            true,
            false,
            $quantity
        );

        $net = $productData['price_without_reduction_without_tax'] ?? $this->calculateProductPrice(
            $productData['id_product'],
            $productData['id_product_attribute'],
            false,
            false,
            $quantity
        );

        return $this->createPrice($net, $gross);
    }

    public function readRelatedProductPromoPrice(array $productData, int $quantity): \izi\item\Price
    {
        $gross = $productData['price'] ?? $this->calculateProductPrice(
            $productData['id_product'],
            $productData['id_product_attribute'],
            true,
            true,
            $quantity
        );

        $net = $productData['price_tax_exc'] ?? $this->calculateProductPrice(
            $productData['id_product'],
            $productData['id_product_attribute'],
            false,
            true,
            $quantity
        );

        return $this->createPrice($net, $gross);
    }

    public function mapConsents(): array
    {
        $consents = [];
        $context = \Context::getContext();

        $selectedRequired = explode(',', $this->getConfiguration('INPOST_PAY_terms_options_required'));
        $requiredText = $this->getConfiguration('INPOST_PAY_terms_options_required_text');

        $selectedRequiredOnce = explode(',', $this->getConfiguration('INPOST_PAY_terms_options_required_once'));
        $requiredOnceText = $this->getConfiguration('INPOST_PAY_terms_options_required_once_text');

        $selectedAdditional = explode(',', $this->getConfiguration('INPOST_PAY_terms_options_additional'));
        $requiredAdditionalText = $this->getConfiguration('INPOST_PAY_terms_options_additional_text');

        $cmsPages = \CMS::getCMSPages($this->cart->id_lang, null, true, $this->cart->id_shop);

        $consentId = 1;

        foreach ($cmsPages as $page) {
            $cmsId = $page['id_cms'];
            $link = $context->link->getCMSLink($cmsId, $page['link_rewrite'], null, $this->cart->id_lang, $this->cart->id_shop);

            if (in_array($cmsId, $selectedRequired, false)) {
                $consents[] = [
                    'consent_id' => $consentId++,
                    'consent_link' => $link,
                    'consent_description' => $requiredText,
                    'consent_version' => 1,
                    'requirement_type' => 'REQUIRED_ALWAYS',
                ];
            } elseif (in_array($cmsId, $selectedRequiredOnce, false)) {
                $consents[] = [
                    'consent_id' => $consentId++,
                    'consent_link' => $link,
                    'consent_description' => $requiredOnceText,
                    'consent_version' => 1,
                    'requirement_type' => 'REQUIRED_ONCE',
                ];
            } elseif (in_array($cmsId, $selectedAdditional, false)) {
                $consents[] = [
                    'consent_id' => $consentId++,
                    'consent_link' => $link,
                    'consent_description' => $requiredAdditionalText,
                    'consent_version' => 1,
                    'requirement_type' => 'OPTIONAL',
                ];
            }
        }

        return $consents;
    }

    public function readPaymentType(): array
    {
        $methods = [];

        if ($this->getConfiguration('INPOST_PAY_payment_aion')) {
            $methods = [
                'CARD',
                'CARD_TOKEN',
                'APPLE_PAY',
                'GOOGLE_PAY',
                'BLIK_CODE',
                'BLIK_TOKEN',
                'PAY_BY_LINK',
                'SHOPPING_LIMIT',
                'DEFERRED_PAYMENT',
            ];
        }
        if ($this->getConfiguration('INPOST_PAY_payment_inpost')) {
            $methods[] = 'CASH_ON_DELIVERY';
        }

        return $methods;
    }

    private function getBrowserId()
    {
        $browserId = Storage::findSession('BrowserId');
        if (!$browserId && isset($_COOKIE['BrowserId'])) {
            $browserId = $_COOKIE['BrowserId'];
        }
        Logger::log('BROWSER_ID: ' . $browserId);

        return $browserId ?: null;
    }

    private function calculateProductPrice(
        int $productId,
        int $combinationId = null,
        bool $withTax = true,
        bool $withReduction = true,
        int $quantity = 1,
        int $customizationId = null
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

        if (is_callable($context, 'getComputingPrecision')) {
            return $context->getComputingPrecision();
        }

        if (defined('_PS_PRICE_COMPUTE_PRECISION_')) {
            return _PS_PRICE_COMPUTE_PRECISION_;
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

    private function getRelatedProductsLimit(): ?int
    {
        $config = $this->getConfiguration('INPOST_PAY_related_count');

        return false === $config || '' === $config
            ? null
            : (int) $config;
    }
}
