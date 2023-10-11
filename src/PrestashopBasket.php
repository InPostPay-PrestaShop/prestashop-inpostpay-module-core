<?php

namespace izi\prestashop;

use izi\BasketIdentification;
use izi\Storage;
use izi\PriceNumber;

class PrestashopBasket extends PrestashopBaseMap
{
    protected $basketBasePriceNet = 0;
    protected $basketBasePriceGross = 0;
    protected $basketBasePriceVat = 0;

    protected $basketPromoPriceNet = 0;
    protected $basketPromoPriceGross = 0;
    protected $basketPromoPriceVat = 0;

    protected $relatedProductIds = [];

    public static $hasCoupons = false;
    public static $couponError = false;

    public static function getBasket($cart = null)
    {
        $prestashopBasket = new self();

        if ($cart == null) {
            return $prestashopBasket->mapBasket(\Context::getContext()->cart);
        }

        return $prestashopBasket->mapBasket($cart);
    }

    public function mapBasket($cart)
    {
        $basket = new \izi\item\Basket();
        if (!$cart) {
            return $basket;
        }

        $basket->products = $this->mapProducts($cart->getProducts(), $cart->id_lang);
        $basket->summary = $this->mapSummary($cart);

        $basket->promo_codes = $this->mapPromoCodes($cart);
        $deliveryPrice = new PrestaDeliveryPrice();
        $basket->delivery = $deliveryPrice->mapDelivery($cart);
        $basket->consents = $this->mapConsents();

        $basket->related_products = $this->mapRelatedProducts($cart->id_lang, $cart->getProducts());

        $browserId = Storage::findSession('BrowserId');
        if (!$browserId && isset($_COOKIE['BrowserId'])) {
            $browserId = $_COOKIE['BrowserId'];
        }
        Logger::log('BROWSER_ID: ' . $browserId);
        if ($browserId) {
            $basket->browser_id = $browserId;
        } else {
            //            Logger::log('COOKIE: ' . print_r($_COOKIE, true));
            //            Logger::log('SESSION: ' . print_r($_SESSION, true));
        }

        return $basket;
    }

    public function mapProducts($products, $idLang)
    {
        $array = [];

        foreach ($products as $product) {
            try {
                $array[] = $this->mapCartProduct($product, $idLang);
            } catch (\Exception $e) {
                Logger::log($e->getMessage());
            }
        }

        return $array;
    }

    public function mapCartProduct($productData, $idLang)
    {
        $prestashopProduct = new \Product($productData['id_product'], true, $idLang);

        $product = $this->mapProductData($prestashopProduct, $idLang, $productData, $productData['id_product_attribute']);

        $this->collectRelatedProductIds($prestashopProduct, $idLang);

        $product->quantity = $this->readQuantity($productData, $prestashopProduct, $idLang);
        $product->base_price = $this->readCartProductBasePrice($prestashopProduct, $productData);
        $product->promo_price = $this->readCartProductPromoPrice($prestashopProduct, $productData);

        return $product;
    }

    public function readCartProductBasePrice($prestashopProduct, $productData)
    {
        $price = new \izi\item\Price();

        $gross = $prestashopProduct->getPriceWithoutReduct(false, null, 6);
        $net = $prestashopProduct->getPriceWithoutReduct(true, null, 6);

        $basketGross = PriceNumber::parse($gross);
        $basketNet = PriceNumber::parse($net);

        $this->basketBasePriceGross += $basketGross * $productData['cart_quantity'];
        $this->basketBasePriceNet += $basketNet * $productData['cart_quantity'];
        $this->basketBasePriceVat += ($basketGross - $basketNet) * $productData['cart_quantity'];

        $price->gross = number_format($gross, 2, '.', '');
        $price->net = number_format($net, 2, '.', '');
        $theTax = $this->makeTax($gross, $net);
        Logger::log("THE TAX IS {$theTax}");
        $price->vat = number_format($theTax, 2, '.', '');

        return $price;
    }

    public function readCartProductPromoPrice($prestashopProduct, $productData)
    {
        $price = new \izi\item\Price();

        $gross = $prestashopProduct->getPrice(true, null, 6);
        $net = $prestashopProduct->getPrice(false, null, 6);

        $basketGross = PriceNumber::parse($gross);
        $basketNet = PriceNumber::parse($net);

        $this->basketPromoPriceGross += $basketGross * $productData['cart_quantity'];
        $this->basketPromoPriceNet += $basketNet * $productData['cart_quantity'];
        $this->basketPromoPriceVat += ($basketGross - $basketNet) * $productData['cart_quantity'];

        $price->gross = number_format($gross, 2, '.', '');
        $price->net = number_format($net, 2, '.', '');
        $price->vat = number_format($this->makeTax($gross, $net), 2, '.', '');

        return $price;
    }

    public function mapProductData($prestashopProduct, $idLang, $rawData, $variation)
    {
        $product = new \izi\item\Product();

        if ($prestashopProduct->id)
        $product->product_id = $prestashopProduct->id . '.' . $variation;
        $product->product_category = $prestashopProduct->getDefaultCategory();
        $product->ean = $prestashopProduct->reference;
        $product->product_name = $prestashopProduct->name;
        $product->product_description = substr(strip_tags($prestashopProduct->description), 0, 1000);
        $product->product_link = \Context::getContext()->link->getProductLink($prestashopProduct->id);

        $product->product_image = $this->readProductImage($prestashopProduct, ($rawData['id_product_attribute'] ?? null));

        $product->variants = $this->mapProductVariables($prestashopProduct, $idLang);
        $product->product_attributes = []; //$this->mapProductAttributes($prestashopProduct);

        return $product;
    }

    public function readProductImage($prestashopProduct, $attributeId)
    {
        $image_type = 'small_default';
        $linkRewrite = isset($prestashopProduct->link_rewrite) ? $prestashopProduct->link_rewrite : $prestashopProduct->name;
        if ($attributeId) {
            $db = \Db::getInstance(_PS_USE_SQL_SLAVE_);
            $request = 'SELECT `id_image` FROM `' . _DB_PREFIX_ . 'product_attribute_image` WHERE id_product_attribute = "' . $attributeId . '";';
            $imageId = $db->getValue($request, false);
            if ($imageId) {
                return \Context::getContext()->link->getImageLink($linkRewrite, $imageId, $image_type);
            }
        }

        $img = $prestashopProduct->getCover($prestashopProduct->id);
        if ($img) {
            return \Context::getContext()->link->getImageLink($linkRewrite, (int)$img['id_image'], $image_type);
        }

        return '';
    }

    public function mapProductVariables($prestashopProduct, $id_lang)
    {
        $array = [];

        $map = [];
        foreach ($prestashopProduct->getAttributeCombinations($id_lang, true) as $attribute) {
            if (!isset($map[$attribute['id_attribute_group']]) || !is_array($map[$attribute['id_attribute_group']])) {
                $map[$attribute['id_attribute_group']] = [];
            }

            $map[$attribute['id_attribute_group']][] = $attribute;
        }

        foreach ($map as $item) {
            $array[] = $this->mapProductVariable($item);
        }

        return $array;
    }

    public function mapProductVariable($item)
    {
        $variant = new \izi\item\Variant();

        $variant->variant_id = $item[0]['id_attribute_group'];
        $variant->variant_name = $item[0]['group_name'];

        $array = [];
        foreach ($item as $attribute) {
            $array[] = $attribute['attribute_name'];
        }

        $variant->variant_values = implode(", ", $array);

        $variant->variant_description = "";
        $variant->variant_type = "";

        return $variant;
    }

    public function readQuantity($productData, $prestashopProduct, $idLang)
    {
        $quantity = $this->readStockQuantity($prestashopProduct, $idLang, $productData);

        $quantity->quantity = $productData['cart_quantity'];

        return $quantity;
    }

    public function readStockQuantity($prestashopProduct, $idLang, $productData)
    {
        $quantity = new \izi\item\Quantity();

        $quantity->quantity_type = "INTEGER";
        $quantity->quantity_unit = "pcs";

        $availableQuantity = \StockAvailable::getQuantityAvailableByProduct($prestashopProduct->id, $productData['id_product_attribute']);

        $quantity->available_quantity = $availableQuantity ?: 99;
        $quantity->max_quantity = $availableQuantity ?: 99;

        return $quantity;
    }

    public function mapPromoCodes($cart)
    {
        $array = [];

        foreach ($cart->getCartRules() as $rule) {
            $array[] = $this->mapPromoCode($rule);
        }

        return $array;
    }

    public function mapPromoCode($rule)
    {
        $promoCode = new \izi\item\PromoCode();

        $promoCode->name = $rule['description'];
        $promoCode->promo_code_value = $rule['code'];

        return $promoCode;
    }


    public function mapSummary($cart)
    {
        $summary = new \izi\item\Summary();

        $summary->basket_base_price = $this->readSummaryBasketBasePrice($cart);
        $summary->basket_final_price = $this->readSummaryBasketFinalPrice($cart);
        $summary->basket_promo_price = $this->readSummaryBasketPromoPrice($cart);

        $summary->currency = \Context::getContext()->currency->iso_code;
        $summary->basket_expiration_date = $this->readBasketExpirationDate();
        $summary->basket_additional_information = '';
        $summary->payment_type = $this->readPaymentType();

        if (self::$hasCoupons) {
            if (self::$couponError) {
                $summary->basket_notice = [
                    'type' => 'ERROR',
                    'description' => 'Kod jest nieaktywny lub nieprawidłowy'
                ];
            } else {
                $summary->basket_notice = [
                    'type' => 'ATTENTION',
                    'description' => 'Kod został aktywowany'
                ];
            }
        }

        return $summary;
    }

    public function readBasketExpirationDate()
    {
        return date("Y-m-d\TH:i:s.000\Z", strtotime('+2 days'));
    }

    public function readSummaryBasketBasePrice($cart)
    {
        $price = new \izi\item\Price();

        $price->gross = number_format(PriceNumber::toFloat($this->basketBasePriceGross), 2, '.', '');
        $price->net = number_format(PriceNumber::toFloat($this->basketBasePriceNet), 2, '.', '');
        $price->vat = number_format(PriceNumber::toFloat($this->basketBasePriceVat), 2, '.', '');

        return $price;
    }

    public function readSummaryBasketPromoPrice($cart)
    {
        $price = new \izi\item\Price();

        $price->gross = number_format(PriceNumber::toFloat($this->basketPromoPriceGross), 2, '.', '');
        $price->net = number_format(PriceNumber::toFloat($this->basketPromoPriceNet), 2, '.', '');
        $price->vat = number_format(PriceNumber::toFloat($this->basketPromoPriceVat), 2, '.', '');

        return $price;
    }

    public function readSummaryBasketFinalPrice($cart)
    {
        $price = new \izi\item\Price();

        $gross = $cart->getOrderTotal(true, \Cart::BOTH_WITHOUT_SHIPPING);
        $net = $cart->getOrderTotal(false, \Cart::BOTH_WITHOUT_SHIPPING);

        $price->gross = number_format($gross, 2, '.', '');
        $price->net = number_format($net, 2, '.', '');
        $price->vat = number_format($this->makeTax($gross, $net), 2, '.', '');

        return $price;
    }

    public function makeTax($gross, $net)
    {
        return number_format($gross - $net, 6, '.', '');
    }

    public function mapRelatedProducts($id_lang, $cartContents)
    {
        $array = [];

        foreach ($this->relatedProductIds as $value) {
            foreach ($cartContents as $productData) {
                if ($productData['id_product'] == $value['id']) {
                    continue 2;
                }
            }
            $array[] = $this->mapRelatedProduct($value['id'], $id_lang, $value['variation']);
        }

        return $array;
    }

    public function mapRelatedProduct($productId, $idLang, $variation)
    {
        $prestashopProduct = new \Product($productId, false, $idLang);

        $product = $this->mapProductData($prestashopProduct, $idLang, [], $variation);
        $product->base_price = $this->readRelatedProductBasePrice($prestashopProduct);
        $product->promo_price = $this->readRelatedProductPromoPrice($prestashopProduct);

        $product->quantity = $this->readStockQuantity($prestashopProduct, $idLang, ['id_product_attribute' => 0]);
        $product->quantity->quantity = 1;

        return $product;
    }

    public function readRelatedProductBasePrice($prestashopProduct)
    {
        $price = new \izi\item\Price();

        $gross = $prestashopProduct->getPriceWithoutReduct(false, null, 6);
        $net = $prestashopProduct->getPriceWithoutReduct(true, null, 6);

        $price->gross = number_format($gross, 2, '.', '');
        $price->net = number_format($net, 2, '.', '');
        $price->vat = number_format($this->makeTax($gross, $net), 2, '.', '');

        return $price;
    }

    public function readRelatedProductPromoPrice($prestashopProduct)
    {
        $price = new \izi\item\Price();

        $gross = $prestashopProduct->getPrice(true, null, 6);
        $net = $prestashopProduct->getPrice(false, null, 6);

        $price->gross = number_format($gross, 2, '.', '');
        $price->net = number_format($net, 2, '.', '');
        $price->vat = number_format($this->makeTax($gross, $net), 2, '.', '');

        return $price;
    }

    public function collectRelatedProductIds($prestashopProduct, $idLang)
    {
        $relatedProducts = $prestashopProduct->getAccessories($idLang);

        if (!empty($relatedProducts)) {
            foreach ($relatedProducts as $relatedProduct) {
                $this->relatedProductIds[] = [
                    'id' => $relatedProduct['id_product'],
                    'variation' => $relatedProduct['id_product_attribute']
                ];
            }
        }
    }

    public function mapConsents()
    {
        $consents = [];
        $context = \Context::getContext();

        $selectedRequired = explode(',', \Configuration::get('INPOST_PAY_terms_options_required'));
        $requiredText = \Configuration::get('INPOST_PAY_terms_options_required_text');

        $selectedRequiredOnce = explode(',', \Configuration::get('INPOST_PAY_terms_options_required_once'));
        $requiredOnceText = \Configuration::get('INPOST_PAY_terms_options_required_once_text');

        $selectedAdditional = explode(',', \Configuration::get('INPOST_PAY_terms_options_additional'));
        $requiredAdditionalText = \Configuration::get('INPOST_PAY_terms_options_additional_text');

        foreach (\CMS::getCMSPages((int)\Configuration::get('PS_LANG_DEFAULT'), 1, true) as $page) {
            $link = $context->link->getCMSLink($page['id_cms'], $page['link_rewrite']);
            if (in_array($link, $selectedRequired)) {
                $consents[] = [
                    "consent_id" => count($consents) + 1,
                    "consent_link" => $link,
                    "consent_description" => $requiredText,
                    "consent_version" => 1,
                    "requirement_type" => "REQUIRED_ALWAYS"
                ];
            } else if (in_array($link, $selectedRequiredOnce)) {
                $consents[] = [
                    "consent_id" => count($consents) + 1,
                    "consent_link" => $link,
                    "consent_description" => $requiredOnceText,
                    "consent_version" => 1,
                    "requirement_type" => "REQUIRED_ONCE"
                ];
            } else if (in_array($link, $selectedAdditional)) {
                $consents[] = [
                    "consent_id" => count($consents) + 1,
                    "consent_link" => $link,
                    "consent_description" => $requiredAdditionalText,
                    "consent_version" => 1,
                    "requirement_type" => "OPTIONAL"
                ];
            }
        }
        return $consents;
    }

    public function readPaymentType()
    {
        $methods = [];

        if (\Configuration::get('INPOST_PAY_payment_aion')) {
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
        if (\Configuration::get('INPOST_PAY_payment_inpost')) {
            $methods[] = 'CASH_ON_DELIVERY';
        }
        return $methods;
    }
}
