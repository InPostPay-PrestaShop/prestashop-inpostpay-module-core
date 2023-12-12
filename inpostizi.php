<?php

use izi\prestashop\InpostIziPayPrestashop;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/BackendForm.php';

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Inpostizi extends PaymentModule
{
    use BackendForm;

    private $firstRender = true;
    private $updatedCartIds = [];

    public function __construct()
    {
        $this->name = 'inpostizi';
        $this->version = '1.3.15';
        $this->author = 'InPost S.A.';
        $this->tab = 'payments_gateways';

        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '1.7.99.99',
        ];

        parent::__construct();

        $this->displayName = $this->l('InPost Pay');
    }

    /**
     * @param array{cart: \Cart} $params
     *
     * @return array
     */
    public function hookPaymentOptions(array $params)
    {
        $cart = $params['cart'];

        if (false === \Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart)) {
            return [];
        }

        $paymentOptions = [];

        $button = $this->showInpostiziBindButton(
            null,
            '',
            'dark' === \Configuration::get('INPOST_PAY_background_cart'),
            'yellow' === \Configuration::get('INPOST_PAY_variant_cart'),
            true,
            \Configuration::get('INPOST_PAY_alignment_cart'),
            \izi\InPostIzi::BINDING_PLACE_ORDER_CREATE
        );

        $paymentOptions[] = (new PaymentOption())
            ->setModuleName($this->name)
            ->setCallToActionText($this->l('Pay with InPost Pay'))
            ->setBinary(true)
            ->setAdditionalInformation($button);

        return $paymentOptions;
    }

    private function checkCurrency(Cart $cart)
    {
        $currency_order = new \Currency($cart->id_currency);
        /** @var array $currencies_module */
        $currencies_module = $this->getCurrency($cart->id_currency);

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
    public function install()
    {
        if (71000 < PHP_VERSION_ID) {
            $this->_errors[] = $this->l('This module requires PHP 7.1 or later.');

            return false;
        }

        Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'inpostizi_basket_session` (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                session_id TEXT,
                confirmation_response TEXT,
                cart_id VARCHAR(255),
                order_id INTEGER,
                order_details TEXT,
                redirect_url VARCHAR(255),
                basket_cache TEXT,
                coupons TEXT,
                event BIT(1),
                redirected SMALLINT(1) DEFAULT 0,
                PRIMARY KEY  (id)
            ) DEFAULT CHARSET=utf8;
        ');

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install() &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('displayShoppingCartFooter') &&
            $this->registerHook('actionCartSave') &&
            $this->registerHook('displayAdminOrderSide') &&
            $this->registerHook('actionObjectInPostShipmentModelAddAfter') &&
            $this->registerHook('displayProductActions') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('actionObjectInPostShipmentModelUpdateAfter') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('actionAjaxDieCartControllerDisplayAjaxUpdateBefore');
    }

    public function hookActionObjectInPostShipmentModelAddAfter($params)
    {
        $this->shipmentChange($params);
    }

    public function hookActionObjectInPostShipmentModelUpdateAfter($params)
    {
        $this->shipmentChange($params);
    }

    private function shipmentChange($data)
    {
        if (!class_exists('InPostShipmentModel')) {
            return;
        }
        $orderId = $data['object']->id_order;

        $units = new \PrestaShopCollection(InPostShipmentModel::class);
        $units->where('id_order', '=', $orderId);
        $units = $units->getResults();
        if (!count($units)) {
            return;
        }
        $numbers = [];
        foreach ($units as $shipment) {
            $numbers[] = $shipment->tracking_number;
        }
        $izi = InpostIziPayPrestashop::getInstance();
        $status = 'Wysłano';
        $izi->orderEvent($orderId, $status, $numbers);
        \izi\prestashop\Logger::log('TRACKING NUMBERS: ' . print_r($numbers, true));
    }

    public function hookDisplayProductActions(array $params)
    {
        if (!\Configuration::get('INPOST_PAY_show_button_details')) {
            return '';
        }

        return $this->showInpostiziBindButton(
            $params['product']->id,
            '',
            'dark' === \Configuration::get('INPOST_PAY_background_details'),
            'yellow' === \Configuration::get('INPOST_PAY_variant_details'),
            false,
            \Configuration::get('INPOST_PAY_alignment_details'),
            \izi\InPostIzi::BINDING_PLACE_PRODUCT_CARD
        );
    }

    public function hookDisplayFooterProduct(array $params)
    {
        if (!\Configuration::get('INPOST_PAY_show_button_details')) {
            return '';
        }

        return $this->showInpostiziBindButton(
            is_array($params['product']) ? $params['product']['id'] : $params['product']->id,
            '',
            'dark' === \Configuration::get('INPOST_PAY_background_details'),
            'yellow' === \Configuration::get('INPOST_PAY_variant_details'),
            false,
            \Configuration::get('INPOST_PAY_alignment_details'),
            \izi\InPostIzi::BINDING_PLACE_PRODUCT_CARD
        );
    }

    public function hookDisplayShoppingCart()
    {
        return $this->hookDisplayShoppingCartFooter();
    }

    public function hookDisplayShoppingCartFooter()
    {
        if (!\Configuration::get('INPOST_PAY_show_button_cart')) {
            return '';
        }

        return $this->showInpostiziBindButton(
            null,
            '',
            'dark' === \Configuration::get('INPOST_PAY_background_cart'),
            'yellow' === \Configuration::get('INPOST_PAY_variant_cart'),
            true,
            \Configuration::get('INPOST_PAY_alignment_cart'),
            \izi\InPostIzi::BINDING_PLACE_BASKET_SUMMARY
        );
    }

    private function showInpostiziBindButton(
        $productId,
        $variationId = '',
        $dark = false,
        $yellow = false,
        $cart = false,
        $float = 'left',
        $bindingPlace = ''
    ) {
        static $alreadyShown;

        if ($alreadyShown) {
            return '';
        }

        $alreadyShown = true;

        if ($this->firstRender) {
            InpostIziPayPrestashop::getInstance()->getController()->basketBindingGet(true);
            $this->firstRender = false;
        }

        \izi\prestashop\Logger::log('Display button start!');
        if (!isset($_COOKIE['izi_show']) && isset($_GET['showIzi']) && $_GET['showIzi'] == 'true') {
            $_COOKIE['izi_show'] = 'true';
            setcookie('izi_show', 'true');
        }
        \izi\prestashop\Logger::log('Cookie present!');
        $hideFunctionality = \Configuration::get('INPOST_PAY_show_izi') == 2 ? 'shown' : 'hidden';
        $show = $_COOKIE['izi_show'] ?? null;
        if ('hidden' === $hideFunctionality && !$show) {
            return '';
        }
        $binding = \izi\prestashop\BindingProvider::getBinding();
        $maskedPhoneNumber = ($binding && isset($binding->basket_linked) && $binding->basket_linked) && isset($binding->client_details, $binding->client_details->masked_phone_number) ? $binding->client_details->masked_phone_number : '';
        $name = ($binding && isset($binding->basket_linked) && $binding->basket_linked) && isset($binding->client_details, $binding->client_details->name) ? $binding->client_details->name : '';
        $inpost_basket_id = ($binding && isset($binding->basket_linked) && $binding->basket_linked) && isset($binding->inpost_basket_id) ? $binding->inpost_basket_id : '';

        $context = \Context::getContext();
        $id_cart = $context->cookie->id_cart;

        if ($id_cart == '') {
            $id_cart = \Tools::getValue('id_cart');
        }

        $theCart = new \Cart($id_cart);
        $products = $theCart->getProducts(true);
        $nbTotalProducts = 0;

        foreach ($products as $product) {
            $nbTotalProducts += (int) $product['cart_quantity'];
        }

        $html = \izi\InPostIzi::render(
            $productId,
            $name,
            $maskedPhoneNumber,
            $inpost_basket_id,
            false,
            false,
            $variationId,
            $nbTotalProducts,
            $dark,
            $yellow,
            $cart,
            $float,
            $bindingPlace
        );

        $style = '';
        if ($cart) {
            $style .= (\Configuration::get('INPOST_PAY_margin_cart_left') ? 'margin-left: ' . \Configuration::get('INPOST_PAY_margin_cart_left') . 'px;' : '');
            $style .= (\Configuration::get('INPOST_PAY_margin_cart_right') ? 'margin-right: ' . \Configuration::get('INPOST_PAY_margin_cart_right') . 'px;' : '');
            $style .= (\Configuration::get('INPOST_PAY_margin_cart_up') ? 'margin-top: ' . \Configuration::get('INPOST_PAY_margin_cart_up') . 'px;' : '');
            $style .= (\Configuration::get('INPOST_PAY_margin_cart_down') ? 'margin-bottom: ' . \Configuration::get('INPOST_PAY_margin_cart_down') . 'px;' : '');
        }
        $this->context->smarty->assign([
            'style' => $style,
            'mymodule_izi_html' => $html,
        ]);

        return $this->display(__FILE__, 'mymodule.tpl');
    }

    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerJavascript(
            'inpostizi-javascript',
            $this->_path . 'views/js/prestashopizi.js',
            [
                'position' => 'bottom',
                'priority' => 101,
            ]
        );

        $this->context->controller->registerJavascript(
            'inpostizi.js',
            'https://izi.inpost.pl/inpostizi.js',
            [
                'position' => 'bottom',
                'priority' => 100,
                'server' => 'remote',
            ]
        );
    }

    /**
     * @param array{cart: \Cart} $params
     */
    public function hookActionCartSave(array $params)
    {
        if ($this->context->controller instanceof \ModuleFrontControllerCore && $this === $this->context->controller->module) {
            return;
        }

        $this->onCartUpdated($params['cart']);
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
        $orderData = \izi\prestashop\CartSession::getOrderData($params['id_order']);
        if (!$orderData) {
            return '';
        }

        $orderData = json_decode($orderData, false);

        $this->context->smarty->assign([
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
        $basketId = \izi\prestashop\CartSession::getBasketIdByCartId($params['order']->id_cart);

        if (null === $basketId) {
            return;
        }

        $controller = \izi\prestashop\InpostIziPayPrestashop::getInstance()->getController();

        if ($this->name !== $params['order']->module) {
            $controller->basketBindingDelete($basketId);
        }

        if ($basketId === \izi\BasketIdentification::get()) {
            \izi\BasketIdentification::drop();
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

    private function sendUpdatedCartsData(): void
    {
        foreach ($this->updatedCartIds as $cartId) {
            try {
                $this->upsertCartData($cartId);
            } catch (\Throwable $throwable) {
                \izi\prestashop\Logger::log(sprintf(
                    'Could not update basket #%d: %s at %s:%d.',
                    $cartId,
                    $throwable->getMessage(),
                    $throwable->getFile(),
                    $throwable->getLine()
                ));
            }
        }
    }

    private function upsertCartData(int $cartId)
    {
        if (!\Validate::isLoadedObject($cart = new \Cart($cartId))) {
            return;
        }

        $izi = \izi\prestashop\InpostIziPayPrestashop::getInstance();

        if (null === $basketId = $this->getBasketId($cartId)) {
            return;
        }

        \izi\prestashop\Logger::log(sprintf('Sending updated cart #%d data.', $cartId));

        $basket = \izi\prestashop\PrestashopBasket::createForCart($cart, $basketId);

        $izi->basketPut(false, false, $basket);
    }

    private function getBasketId(int $cartId)
    {
        if (!$this->context->controller instanceof \FrontControllerCore || $cartId !== (int) $this->context->cart->id) {
            return \izi\prestashop\CartSession::getBasketIdByCartId($cartId);
        }

        \izi\prestashop\CartSession::storeCurrent();

        return \izi\BasketIdentification::get();
    }
}
