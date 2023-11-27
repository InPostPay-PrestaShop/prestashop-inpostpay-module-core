<?php

namespace izi\prestashop;

use izi\item\Quantity;
use izi\prestashop\traits\PriceFactoryTrait;
use izi\Storage;

class PrestashopBasket extends PrestashopBaseMap
{
    use PriceFactoryTrait;

    private $cart;

    public static $hasCoupons = false;
    public static $couponError = false;

    public function __construct(\Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * @return \izi\item\Basket
     */
    public static function getBasket(\Cart $cart = null)
    {
        if (null === $cart) {
            $cart = \Context::getContext()->cart;
        }

        return (new self($cart))->mapBasket();
    }

    /**
     * @return \izi\item\Basket
     */
    public function mapBasket()
    {
        $basket = new \izi\item\Basket();

        if (!\Validate::isLoadedObject($this->cart)) {
            return $basket;
        }

        $cart = $this->cart;
        $cartProducts = $cart->getProducts();

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
    public function mapProducts(array $products)
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

    /**
     * @return \izi\item\BasketProduct
     */
    public function mapCartProduct(array $productData)
    {
        $prestashopProduct = new \Product($productData['id_product'], false, $this->cart->id_lang);

        $product = $this->mapProductData($prestashopProduct, $productData);

        $product->quantity = $this->readQuantity($productData, $prestashopProduct);
        $product->base_price = $this->readCartProductBasePrice($productData);
        $product->promo_price = $this->readCartProductPromoPrice($productData);

        return $product;
    }

    /**
     * @return \izi\item\Price
     */
    public function readCartProductBasePrice(array $productData)
    {
        $gross = isset($productData['price_without_reduction'])
            ? $productData['price_without_reduction']
            : $this->calculateProductPrice(
                $productData['id_product'],
                $productData['id_product_attribute'],
                true,
                false,
                $productData['cart_quantity'],
                $productData['id_customization']
            );

        $net = isset($productData['price_without_reduction_without_tax'])
            ? $productData['price_without_reduction_without_tax']
            : $this->calculateProductPrice(
                $productData['id_product'],
                $productData['id_product_attribute'],
                false,
                false,
                $productData['cart_quantity'],
                $productData['id_customization']
            );

        return $this->createPrice($net, $gross);
    }

    /**
     * @return \izi\item\Price
     */
    public function readCartProductPromoPrice(array $productData)
    {
        $gross = isset($productData['price_with_reduction'])
            ? $productData['price_with_reduction']
            : $this->calculateProductPrice(
                $productData['id_product'],
                $productData['id_product_attribute'],
                true,
                true,
                $productData['cart_quantity'],
                $productData['id_customization']
            );

        $net = isset($productData['price_with_reduction_without_tax'])
            ? $productData['price_with_reduction_without_tax']
            : $this->calculateProductPrice(
                $productData['id_product'],
                $productData['id_product_attribute'],
                false,
                true,
                $productData['cart_quantity'],
                $productData['id_customization']
            );

        return $this->createPrice($net, $gross);
    }

    /**
     * @return \izi\item\BasketProduct
     */
    public function mapProductData(\Product $prestashopProduct, array $productData)
    {
        $product = new \izi\item\BasketProduct();

        $combinationId = array_key_exists('id_product_attribute', $productData) ? $productData['id_product_attribute'] : 0;
        $customizationId = array_key_exists('id_customization', $productData) ? $productData['id_customization'] : 0;

        $product->product_id = $prestashopProduct->id . '.' . (int) $combinationId . '.' . (int) $customizationId;
        $product->product_category = $prestashopProduct->getDefaultCategory();
        $product->ean = isset($productData['ean13']) ? $productData['ean13'] : $prestashopProduct->ean13;
        $product->product_name = $prestashopProduct->name;
        $product->product_description = \Tools::substr(trim(strip_tags($prestashopProduct->description)), 0, 1000);
        $product->product_link = \Context::getContext()->link->getProductLink($prestashopProduct, null, null, null, $this->cart->id_lang, null, $combinationId);

        $product->product_image = $this->readProductImage($prestashopProduct, $combinationId);

        $product->variants = $this->mapProductVariables($prestashopProduct);
        $product->product_attributes = [];

        return $product;
    }

    public function readProductImage(\Product $prestashopProduct, $attributeId)
    {
        $image_type = 'small_default';
        $linkRewrite = isset($prestashopProduct->link_rewrite) ? $prestashopProduct->link_rewrite : $prestashopProduct->name;
        if ($attributeId) {
            $db = \Db::getInstance(_PS_USE_SQL_SLAVE_);
            $request = 'SELECT `id_image` FROM `' . _DB_PREFIX_ . 'product_attribute_image` WHERE id_product_attribute = "' . (int) $attributeId . '";';
            $imageId = $db->getValue($request, false);
            if ($imageId) {
                return \Context::getContext()->link->getImageLink($linkRewrite, $imageId, $image_type);
            }
        }

        $img = $prestashopProduct->getCover($prestashopProduct->id);
        if ($img) {
            return \Context::getContext()->link->getImageLink($linkRewrite, (int) $img['id_image'], $image_type);
        }

        return '';
    }

    public function mapProductVariables(\Product $prestashopProduct)
    {
        $result = [];

        $map = [];
        foreach ($prestashopProduct->getAttributeCombinations($this->cart->id_lang, true) as $attribute) {
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

    public function mapProductVariable($item)
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

        if (!$productData['allow_oosp']) {
            $availableQuantity = isset($productData['quantity_available'])
                ? (int) $productData['quantity_available']
                : \StockAvailable::getQuantityAvailableByProduct($prestashopProduct->id, $productData['id_product_attribute']);

            if (0 > $availableQuantity) {
                $availableQuantity = 0;
            }

            $quantity->available_quantity = $availableQuantity;
            $quantity->max_quantity = $availableQuantity;
        }

        return $quantity;
    }

    /**
     * @return \izi\item\PromoCode[]
     */
    public function mapPromoCodes()
    {
        $result = [];

        foreach ($this->cart->getCartRules() as $rule) {
            $result[] = $this->mapPromoCode($rule);
        }

        return $result;
    }

    /**
     * @return \izi\item\PromoCode
     */
    public function mapPromoCode(array $rule)
    {
        $promoCode = new \izi\item\PromoCode();

        $promoCode->name = $rule['description'];
        $promoCode->promo_code_value = $rule['code'];

        return $promoCode;
    }

    /**
     * @param \izi\item\BasketProduct[]
     *
     * @return \izi\item\Summary
     */
    public function mapSummary(array $products)
    {
        $summary = new \izi\item\Summary();

        $summary->basket_base_price = $this->readSummaryBasketBasePrice($products);
        $summary->basket_final_price = $this->readSummaryBasketFinalPrice();

        if (
            [] !== $this->cart->getCartRules(\CartRule::FILTER_ACTION_REDUCTION, false) ||
            [] !== $this->cart->getCartRules(\CartRule::FILTER_ACTION_GIFT, false)
        ) {
            $summary->basket_promo_price = $this->readSummaryBasketPromoPrice();
        } else {
            $summary->basket_promo_price = clone $summary->basket_final_price;
        }

        $summary->currency = 'PLN';
        $summary->basket_expiration_date = $this->readBasketExpirationDate();
        $summary->basket_additional_information = '';
        $summary->payment_type = $this->readPaymentType();

        if (self::$hasCoupons) {
            if (self::$couponError) {
                $summary->basket_notice = [
                    'type' => 'ERROR',
                    'description' => 'Kod jest nieaktywny lub nieprawidłowy',
                ];
            } else {
                $summary->basket_notice = [
                    'type' => 'ATTENTION',
                    'description' => 'Kod został aktywowany',
                ];
            }
        }

        return $summary;
    }

    public function readBasketExpirationDate()
    {
        return date("Y-m-d\TH:i:s.000\Z", strtotime('+2 days'));
    }

    /**
     * @param \izi\item\BasketProduct[] $products
     *
     * @return \izi\item\Price
     */
    public function readSummaryBasketBasePrice(array $products)
    {
        $net = array_reduce($products, function ($sum, \izi\item\BasketProduct $product) {
            return $sum + $this->calculateRowTotal($product->base_price->net, $product->quantity->quantity);
        }, 0.);

        $gross = array_reduce($products, function ($sum, \izi\item\BasketProduct $product) {
            return $sum + $this->calculateRowTotal($product->base_price->gross, $product->quantity->quantity);
        }, 0.);

        return $this->createPrice($net, $gross);
    }

    /**
     * @return \izi\item\Price
     */
    public function readSummaryBasketPromoPrice()
    {
        $gross = $this->cart->getOrderTotal(true, \Cart::ONLY_PRODUCTS);
        $net = $this->cart->getOrderTotal(false, \Cart::ONLY_PRODUCTS);

        return $this->createPrice($net, $gross);
    }

    public function readSummaryBasketFinalPrice()
    {
        $gross = $this->cart->getOrderTotal(true, \Cart::BOTH_WITHOUT_SHIPPING);
        $net = $this->cart->getOrderTotal(false, \Cart::BOTH_WITHOUT_SHIPPING);

        return $this->createPrice($net, $gross);
    }

    /**
     * @return \izi\item\BasketProduct[]
     */
    public function mapRelatedProducts(array $cartProducts)
    {
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

        foreach ($relatedProducts as $relatedProduct) {
            $result[] = $this->mapRelatedProduct($relatedProduct);
        }

        return $result;
    }

    /**
     * @return \izi\item\BasketProduct
     */
    public function mapRelatedProduct(array $productData)
    {
        $prestashopProduct = new \Product($productData['id_product'], false, $this->cart->id_lang);

        $quantity = isset($productData['min_quantity']) ? (int) $productData['min_quantity'] : $prestashopProduct->minimal_quantity;

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

    /**
     * @param int $quantity
     *
     * @return \izi\item\Price
     */
    public function readRelatedProductBasePrice(array $productData, $quantity)
    {
        $gross = isset($productData['price_without_reduction'])
            ? $productData['price_without_reduction']
            : $this->calculateProductPrice(
                $productData['id_product'],
                $productData['id_product_attribute'],
                true,
                false,
                $quantity
            );

        $net = isset($productData['price_without_reduction_without_tax'])
            ? $productData['price_without_reduction_without_tax']
            : $this->calculateProductPrice(
                $productData['id_product'],
                $productData['id_product_attribute'],
                false,
                false,
                $quantity
            );

        return $this->createPrice($net, $gross);
    }

    /**
     * @param int $quantity
     *
     * @return \izi\item\Price
     */
    public function readRelatedProductPromoPrice(array $productData, $quantity)
    {
        $gross = isset($productData['price'])
            ? $productData['price']
            : $this->calculateProductPrice(
                $productData['id_product'],
                $productData['id_product_attribute'],
                true,
                true,
                $quantity
            );

        $net = isset($productData['price_tax_exc'])
            ? $productData['price_tax_exc']
            : $this->calculateProductPrice(
                $productData['id_product'],
                $productData['id_product_attribute'],
                false,
                true,
                $quantity
            );

        return $this->createPrice($net, $gross);
    }

    public function mapConsents()
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

    public function readPaymentType()
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

    /**
     * @param int $productId
     * @param int|null $combinationId
     * @param int|null $customizationId
     * @param int $quantity
     * @param bool $withTax
     * @param bool $withReduction
     *
     * @return float|null
     */
    private function calculateProductPrice(
        $productId,
        $combinationId,
        $withTax = true,
        $withReduction = true,
        $quantity = 1,
        $customizationId = null
    ) {
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

    /**
     * @param float $price
     * @param int $quantity
     *
     * @return float
     */
    private function calculateRowTotal($price, $quantity)
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

    /**
     * @return int
     */
    private function getPriceComputingPrecision()
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
     * @param $key
     *
     * @return false|string
     */
    private function getConfiguration($key)
    {
        return \Configuration::get($key, null, null, $this->cart->id_shop);
    }
}
