<?php

use izi\BasketIdentification;
use izi\item\Basket;
use izi\prestashop\BindingProvider;
use izi\prestashop\CartSession;
use izi\prestashop\Common\BindingPlace;
use izi\prestashop\InpostIziPayPrestashop;
use izi\prestashop\Installer\DatabaseInstaller;
use izi\prestashop\Logger;
use izi\prestashop\PrestashopBasket;
use izi\prestashop\traits\OrderStatusDescriberTrait;
use izi\prestashop\Widget\Alignment;
use izi\prestashop\Widget\FrameStyle;
use izi\prestashop\Widget\Variant;
use izi\prestashop\WidgetConfiguration;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/BackendForm.php';

class Inpostizi extends PaymentModule
{
    use BackendForm;
    use OrderStatusDescriberTrait;

    private $updatedCartIds = [];
    private $shipmentUpdated = false;

    public function __construct()
    {
        $this->name = 'inpostizi';
        $this->version = '1.4.0';
        $this->author = 'InPost S.A.';
        $this->tab = 'payments_gateways';
        $this->bootstrap = true;

        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '1.7.8.99',
        ];

        parent::__construct();

        $this->displayName = $this->l('InPost Pay');

        $this->registerHook('displayExpressCheckout');
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (71000 < PHP_VERSION_ID) {
            $this->_errors[] = $this->l('This module requires PHP 7.1 or later.');

            return false;
        }

        $dbInstaller = new DatabaseInstaller();

        if (!$dbInstaller->install($this)) {
            $this->_errors[] = $this->l('Could not update the database schema.');

            return false;
        }

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install() &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayProductActions') &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('displayShoppingCartFooter') &&
            $this->registerHook('actionCartSave') &&
            $this->registerHook('actionAjaxDieCartControllerDisplayAjaxUpdateBefore') &&
            $this->registerHook('actionObjectCartDeleteBefore') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('displayAdminOrderSide') &&
            $this->registerHook('actionObjectInPostShipmentModelAddAfter') &&
            $this->registerHook('actionObjectInPostShipmentModelUpdateBefore') &&
            $this->registerHook('actionObjectInPostShipmentModelUpdateAfter');
    }

    /**
     * @param int[] $shops shop IDs
     *
     * @return bool
     */
    public function addCheckboxCurrencyRestrictionsForModule(array $shops = [])
    {
        if (!$shops) {
            $shops = Shop::getShops(true, null, true);
        }

        $data = [];

        foreach ($shops as $shopId) {
            if (0 >= $currencyId = (int) \Currency::getIdByIsoCode('PLN', $shopId)) {
                continue;
            }

            $data[] = [
                'id_module' => (int) $this->id,
                'id_shop' => (int) $shopId,
                'id_currency' => $currencyId,
            ];
        }

        return \Db::getInstance()->insert('module_currency', $data);
    }

    /**
     * @param array{object: \InPostShipmentModel} $params
     */
    public function hookActionObjectInPostShipmentModelAddAfter(array $params)
    {
        if (!isset($params['object']) || !$params['object'] instanceof \InPostShipmentModel) {
            return;
        }

        $shipment = $params['object'];

        if (empty($shipment->tracking_number)) {
            return;
        }

        $this->onShipmentUpdated($shipment);
    }

    /**
     * @param array{object: \InPostShipmentModel} $params
     */
    public function hookActionObjectInPostShipmentModelUpdateBefore(array $params)
    {
        if (!isset($params['object']) || !$params['object'] instanceof \InPostShipmentModel) {
            return;
        }

        $shipment = $params['object'];

        if (empty($shipment->tracking_number) || 0 >= $shipment->id) {
            return;
        }

        $previousState = new \InPostShipmentModel($shipment->id);
        if (!\Validate::isLoadedObject($previousState)) {
            return;
        }

        $this->shipmentUpdated = $previousState->tracking_number !== $shipment->tracking_number;
    }

    /**
     * @param array{object: \InPostShipmentModel} $params
     */
    public function hookActionObjectInPostShipmentModelUpdateAfter(array $params)
    {
        if (!$this->shipmentUpdated) {
            return;
        }

        $this->shipmentUpdated = false;
        $this->onShipmentUpdated($params['object']);
    }

    public function hookDisplayProductActions(array $params)
    {
        if (!\Configuration::get('INPOST_PAY_show_button_details')) {
            return '';
        }

        $config = $this->createWidgetConfigurationForProduct($params['product']->id);

        return $this->renderInPostPayWidget($config);
    }

//    public function hookDisplayFooterProduct(array $params)
//    {
//        if (!\Configuration::get('INPOST_PAY_show_button_details')) {
//            return '';
//        }
//
//        $productId = is_array($params['product']) ? $params['product']['id'] : $params['product']->id;
//        $config = $this->createWidgetConfigurationForProduct($productId);
//
//        return $this->renderInPostPayWidget($config);
//    }

//    public function hookDisplayShoppingCart()
//    {
//        return $this->hookDisplayShoppingCartFooter();
//    }

//    public function hookDisplayShoppingCartFooter()
//    {
//        if (!\Configuration::get('INPOST_PAY_show_button_cart')) {
//            return '';
//        }
//
//        $config = $this->createWidgetConfigurationForCart();
//        $styles = iterator_to_array($this->getCartWidgetStyles());
//
//        return $this->renderInPostPayWidget($config, $styles);
//    }

    public function hookDisplayExpressCheckout()
    {
        if (!\Configuration::get('INPOST_PAY_show_button_cart')) {
            return '';
        }

        $config = $this->createWidgetConfigurationForCart();
        $styles = iterator_to_array($this->getCartWidgetStyles());

        return $this->renderInPostPayWidget($config, $styles);
    }

    /**
     * @param array{cart: \Cart} $params
     *
     * @return PaymentOption[]
     */
    public function hookPaymentOptions(array $params)
    {
        $cart = $params['cart'];

        if (false === \Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart->id_currency)) {
            return [];
        }

        $paymentOptions = [];

        $config = $this
            ->createWidgetConfigurationForCart()
            ->setBindingPlace(BindingPlace::OrderCreate());

        if ('' === $button = $this->renderInPostPayWidget($config)) {
            return [];
        }

        $paymentOptions[] = (new PaymentOption())
            ->setModuleName($this->name)
            ->setCallToActionText($this->l('Pay with InPost Pay'))
            ->setBinary(true)
            ->setAdditionalInformation($button);

        return $paymentOptions;
    }

    public function hookActionFrontControllerSetMedia()
    {
        InpostIziPayPrestashop::getInstance();

        $this->context->controller->registerJavascript(
            'inpostizi-javascript',
            "modules/$this->name/views/js/prestashopizi.js",
            [
                'position' => 'bottom',
                'priority' => 101,
            ]
        );

        $this->context->controller->registerJavascript(
            'inpostizi.js',
            InpostIziPayPrestashop::getJsUrl(),
            [
                'position' => 'bottom',
                'priority' => 100,
                'server' => 'remote',
            ]
        );

        \Media::addJsDef([
            'inpostizi_backend_ajax_url' => $this->context->link->getModuleLink($this->name, 'backend'),
            'inpostizi_cart_ajax_url' => $this->context->link->getModuleLink($this->name, 'cart'),
        ]);

        if ($this->context->controller instanceof \ProductControllerCore) {
            $productObject = $this->context->controller->getProduct();

            if (\Validate::isLoadedObject($productObject)) {
                \Media::addJsDef([
                    'inpostizi_product_page_id_product' => $productObject->id,
                ]);
            }
        }
    }

    /**
     * @param array{cart: \Cart} $params
     */
    public function hookActionCartSave(array $params)
    {
        if (!isset($params['cart']) || !$params['cart'] instanceof \Cart) {
            return;
        }

        if ($this->context->controller instanceof \ModuleFrontControllerCore && $this === $this->context->controller->module) {
            return;
        }

        $this->onCartUpdated($params['cart']);
    }

    /**
     * @param array{object: \Cart} $params
     */
    public function hookActionObjectCartDeleteBefore(array $params)
    {
        $cart = isset($params['object']) ? $params['object'] : null;

        if (!$cart instanceof \Cart || !\Validate::isLoadedObject($cart)) {
            return;
        }

        $session = CartSession::getByCartId($cart->id);

        if (null === $session || null === $session->confirmation_response) {
            return;
        }

        InpostIziPayPrestashop::getInstance()
            ->getController()
            ->basketBindingDelete($session->cart_id);
    }

    public function hookActionAjaxDieCartControllerDisplayAjaxUpdateBefore(array $params)
    {
        if (!\Tools::getIsset('addDiscount') && !\Tools::getIsset('deleteDiscount')) {
            return;
        }

        $this->onCartUpdated($this->context->cart);
    }

    public function hookDisplayAdminOrderSide(array $params)
    {
        if (!$orderData = CartSession::getOrderData($params['id_order'])) {
            return '';
        }

        $orderData = json_decode($orderData, false);

        $this->smarty->assign([
            'delivery' => 'APM' === $orderData->delivery->delivery_type ? 'Paczkomat' : 'Kurier',
            'apm' => 'APM' === $orderData->delivery->delivery_type ? $orderData->delivery->delivery_point : '',
        ]);

        return $this->display(__FILE__, 'backend.tpl');
    }

    /**
     * @param array{order: \Order} $params
     */
    public function hookDisplayOrderConfirmation(array $params)
    {
        $basketId = CartSession::getBasketIdByCartId($params['order']->id_cart);

        if (null === $basketId) {
            return;
        }

        $controller = InpostIziPayPrestashop::getInstance()->getController();

        if ($this->name !== $params['order']->module) {
            $controller->basketBindingDelete($basketId, true);
        }

        if ($basketId === BasketIdentification::get()) {
            BasketIdentification::drop();
        }
    }

    /**
     * @param array{order: \Order} $params
     *
     * @return string
     */
    public function hookDisplayPaymentReturn(array $params)
    {
        if ($this->name !== $params['order']->module) {
            return '';
        }

        return '<inpost-thank-you/>';
    }

    private function renderInPostPayWidget(WidgetConfiguration $config, array $styles = [])
    {
//        static $alreadyShown;
//
//        if ($alreadyShown) {
//            return '';
//        }
//
//        $alreadyShown = true;

        if (!$this->shouldRenderWidget()) {
            return '';
        }

        InpostIziPayPrestashop::getInstance()->getController()->basketBindingGet(true);

        $binding = BindingProvider::getBinding();
        $isBasketLinked = $binding && isset($binding->basket_linked) && $binding->basket_linked;

        if (!$isBasketLinked && !$this->checkCurrency($this->context->currency->id)) {
            return '';
        }

        $count = $this->getCartProductsCount();

        if (!$isBasketLinked && 0 >= $count && $config->isBasket()) {
            return '';
        }

        $maskedPhoneNumber = $isBasketLinked && isset($binding->client_details->masked_phone_number) ? $binding->client_details->masked_phone_number : null;
        $name = $isBasketLinked && isset($binding->client_details->name) ? $binding->client_details->name : null;
//        $inpost_basket_id = $isBasketLinked && isset($binding->inpost_basket_id) ? $binding->inpost_basket_id : '';

        $basketId = BasketIdentification::get();

        if (null !== CartSession::getCartOrderRedirectUrl($basketId)) {
            BasketIdentification::drop();
            $basketId = BasketIdentification::get();
        }

        if (!CartSession::getCartConfirmation($basketId)) {
            $maskedPhoneNumber = null;
        }

        $config
            ->setName($name)
            ->setMaskedPhoneNumber($maskedPhoneNumber)
            ->setCount($count);

        $this->smarty->assign([
            'styles' => $styles,
            'attributes' => $config,
        ]);

        return $this->display(__FILE__, 'mymodule.tpl');
    }

    private function checkCurrency($currencyId)
    {
        if (0 >= $currencyId) {
            return false;
        }

        $currency_order = new \Currency($currencyId);
        /** @var array $currencies_module */
        $currencies_module = $this->getCurrency($currencyId);

        if (empty($currencies_module)) {
            return false;
        }

        foreach ($currencies_module as $currency_module) {
            if ($currency_order->id == $currency_module['id_currency']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function shouldRenderWidget()
    {
        if (2 === (int) \Configuration::get('INPOST_PAY_show_izi')) {
            return true;
        }

        if (!empty($this->context->cookie->izi_show)) {
            return true;
        }

        if (!isset($_GET['showIzi']) || 'true' !== $_GET['showIzi']) {
            return false;
        }

        $this->context->cookie->izi_show = true;

        return true;
    }

    private function onCartUpdated(\Cart $cart)
    {
        if (0 >= $cartId = (int) $cart->id) {
            return;
        }

        if ([] === $this->updatedCartIds) {
            register_shutdown_function(function () {
                $this->sendUpdatedCartsData();
            });
        }

        $this->updatedCartIds[$cartId] = $cartId;
    }

    private function sendUpdatedCartsData()
    {
        foreach ($this->updatedCartIds as $cartId) {
            try {
                $this->upsertCartData($cartId);
            } catch (\Exception $exception) {
                Logger::log(sprintf(
                    'Could not update basket #%d: %s at %s:%d.',
                    $cartId,
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                ));
            }
        }
    }

    /**
     * @param int $cartId
     */
    private function upsertCartData($cartId)
    {
        if (!\Validate::isLoadedObject($cart = new \Cart($cartId))) {
            return;
        }

        $izi = InpostIziPayPrestashop::getInstance();

        if (null === $basketId = $this->getBasketId($cartId)) {
            return;
        }

        Logger::log(sprintf('Sending updated cart #%d data.', $cartId));

        $basket = $this->getBasketData($cart, $basketId);

        $izi->basketPut(false, false, $basket);
    }

    /**
     * @param int $cartId
     *
     * @return string|null
     */
    private function getBasketId($cartId)
    {
        $basketId = CartSession::getBasketIdByCartId($cartId);

        if (null !== $basketId || !$this->context->controller instanceof \FrontControllerCore || $cartId !== (int) $this->context->cart->id) {
            return $basketId;
        }

        $currentBasketId = BasketIdentification::get();
        $session = CartSession::getByBasketId($currentBasketId);

        if (null === $session) {
            return null;
        }

        // customer switched his current cart (e.g. using the reorder option)
        $session->session_id = $cartId;
        $session->basket_cache = null;
        $session->update(true);

        return $currentBasketId;
    }

    /**
     * @param Cart $cart
     * @param string $basketId
     *
     * @return Basket
     */
    private function getBasketData(\Cart $cart, $basketId)
    {
        $currency = $this->context->currency;

        if ('PLN' === $this->context->currency->iso_code) {
            return PrestashopBasket::createForCart($cart, $basketId);
        }

        $currencyId = \Currency::getIdByIsoCode('PLN');
        $cart->id_currency = $currencyId;
        $this->context->currency = \Currency::getCurrencyInstance($currencyId);

        try {
            \Cache::clean('getPackageShippingCost_' . (int) $cart->id . '_*');

            return PrestashopBasket::createForCart($cart, $basketId);
        } finally {
            $this->context->currency = $currency;
            \Cache::clean('getPackageShippingCost_' . (int) $cart->id . '_*');
        }
    }

    private function onShipmentUpdated(\InPostShipmentModel $shipment)
    {
        $order = $shipment->getOrder();

        if (null === CartSession::getByCartId($order->id_cart)) {
            return;
        }

        $shipments = (new \PrestaShopCollection(\InPostShipmentModel::class))
            ->where('id_order', '=', $order->id)
            ->sqlWhere('tracking_number IS NOT NULL')
            ->getResults();

        if (empty($shipments)) {
            return;
        }

        $trackingNumbers = array_map(static function (\InPostShipmentModel $shipment) {
            return $shipment->tracking_number;
        }, $shipments);
        $status = $this->getStatusDescription($order);

        InpostIziPayPrestashop::getInstance()->orderEvent($order->id, $status, $trackingNumbers);
    }

    /**
     * @param int $productId
     *
     * @return WidgetConfiguration
     */
    private function createWidgetConfigurationForProduct($productId)
    {
        $minWidth = $this->getWidgetWidth((int) \Configuration::get('INPOST_PAY_min_width_details'));
        $maxWidth = $this->getWidgetWidth((int) \Configuration::get('INPOST_PAY_max_width_details'));

        return (new WidgetConfiguration(BindingPlace::ProductCard(), false))
            ->setProductId((string) $productId)
            ->setLanguage(\izi\prestashop\Widget\Language::tryFrom($this->context->language->iso_code) ?? \izi\prestashop\Widget\Language::En())
            ->setVariant(Variant::tryFrom((string) \Configuration::get('INPOST_PAY_variant_details')) ?? Variant::Secondary())
            ->setDarkMode((bool) \Configuration::get('INPOST_PAY_background_details'))
            ->setAlignment(Alignment::tryFrom((string) \Configuration::get('INPOST_PAY_alignment_details')))
            ->setFrameStyle(FrameStyle::tryFrom((string) \Configuration::get('INPOST_PAY_frame_style_details')))
            ->setMinWidth($minWidth)
            ->setMaxWidth($maxWidth);
    }

    /**
     * @return WidgetConfiguration
     */
    private function createWidgetConfigurationForCart()
    {
        $minWidth = $this->getWidgetWidth((int) \Configuration::get('INPOST_PAY_min_width_cart'));
        $maxWidth = $this->getWidgetWidth((int) \Configuration::get('INPOST_PAY_max_width_cart'));

        return (new WidgetConfiguration(BindingPlace::BasketSummary(), true))
            ->setLanguage(\izi\prestashop\Widget\Language::tryFrom($this->context->language->iso_code) ?? \izi\prestashop\Widget\Language::En())
            ->setVariant(Variant::tryFrom((string) \Configuration::get('INPOST_PAY_variant_cart')) ?? Variant::Secondary())
            ->setDarkMode((bool) \Configuration::get('INPOST_PAY_background_cart'))
            ->setAlignment(Alignment::tryFrom((string) \Configuration::get('INPOST_PAY_alignment_cart')))
            ->setFrameStyle(FrameStyle::tryFrom((string) \Configuration::get('INPOST_PAY_frame_style_cart')))
            ->setMinWidth($minWidth)
            ->setMaxWidth($maxWidth);
    }

    /**
     * @return \Generator<string, string>
     */
    private function getCartWidgetStyles()
    {
        if (0 < $marginLeft = (int) \Configuration::get('INPOST_PAY_margin_cart_left')) {
            yield 'margin-left' => sprintf('%dpx', $marginLeft);
        }

        if (0 < $marginRight = (int) \Configuration::get('INPOST_PAY_margin_cart_right')) {
            yield 'margin-right' => sprintf('%dpx', $marginRight);
        }

        if (0 < $marginTop = (int) \Configuration::get('INPOST_PAY_margin_cart_up')) {
            yield 'margin-top' => sprintf('%dpx', $marginTop);
        }

        if (0 < $marginBottom = (int) \Configuration::get('INPOST_PAY_margin_cart_down')) {
            yield 'margin-bottom' => sprintf('%dpx', $marginBottom);
        }
    }

    /**
     * @param int $width
     *
     * @return int|null
     */
    private function getWidgetWidth($width)
    {
        return WidgetConfiguration::WIDTH_MIN_PX <= $width && WidgetConfiguration::WIDTH_MAX_PX >= $width ? $width : null;
    }

    /**
     * @return int|null
     */
    private function getCartProductsCount()
    {
        if (!isset($this->context->cart)) {
            return null;
        }

        return array_reduce($this->context->cart->getProducts(), static function ($count, array $product) {
            return $count + (int) $product['cart_quantity'];
        }, 0);
    }
}
