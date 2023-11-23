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

    public function __construct()
    {
        $this->name = 'inpostizi';
        $this->tab = 'checkout';
        $this->version = '1.3.14';

        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '1.7.99.99',
        ];

        parent::__construct();
    }

    /**
     * @param array $params
     *
     * @return array Should always return an array
     */
    public function hookPaymentOptions(array $params)
    {
        /** @var \Cart $cart */
        $cart = $params['cart'];

        if (false === \Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart)) {
            return [];
        }

        $paymentOptions = [];

        $inPostPay = new PaymentOption();
        $inPostPay->setModuleName($this->name);
        $inPostPay->setCallToActionText($this->l('InPost Pay'));
        $inPostPay->setBinary(true);

        $paymentOptions[] = $inPostPay;

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

    public function install()
    {
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
                basket_cached TEXT,
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
            $this->registerHook('actionPresentCart') &&
            $this->registerHook('displayAdminOrderSide') &&
            $this->registerHook('actionObjectInPostShipmentModelAddAfter') &&
            $this->registerHook('actionCartUpdateQuantityBefore') &&
            $this->registerHook('displayProductActions') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('actionObjectInPostShipmentModelUpdateAfter');
    }

    public function uninstall()
    {
        return parent::uninstall();
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

    public function hookDisplayProductActions($params)
    {
        if (\Configuration::get('INPOST_PAY_show_button_details') == 1) {
            return $this->showInpostiziBindButton(
                $params['product']->id,
                '',
                \Configuration::get('INPOST_PAY_background_details') == 'dark',
                \Configuration::get('INPOST_PAY_variant_details') == 'yellow',
                false,
                \Configuration::get('INPOST_PAY_alignment_details'),
                \izi\InPostIzi::BINDING_PLACE_PRODUCT_CARD
            );
        }
    }

    public function hookDisplayFooterProduct($params)
    {
        if (\Configuration::get('INPOST_PAY_show_button_details') == 1) {
            return $this->showInpostiziBindButton(
                is_array($params['product']) ? $params['product']['id'] : $params['product']->id,
                '',
                \Configuration::get('INPOST_PAY_background_details') == 'dark',
                \Configuration::get('INPOST_PAY_variant_details') == 'yellow',
                false,
                \Configuration::get('INPOST_PAY_alignment_details'),
                \izi\InPostIzi::BINDING_PLACE_PRODUCT_CARD
            );
        }
    }

    public function hookDisplayShoppingCart($params)
    {
        return $this->hookDisplayShoppingCartFooter($params);
    }

    public function hookDisplayShoppingCartFooter($params)
    {
        if (\Configuration::get('INPOST_PAY_show_button_cart') == 1) {
            return $this->showInpostiziBindButton(
                null,
                '',
                \Configuration::get('INPOST_PAY_background_cart') == 'dark',
                \Configuration::get('INPOST_PAY_variant_cart') == 'yellow',
                true,
                \Configuration::get('INPOST_PAY_alignment_cart'),
                \izi\InPostIzi::BINDING_PLACE_BASKET_SUMMARY
            );
        }
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
        if (!$alreadyShown) {
            $alreadyShown = true;
        } else {
            return;
        }
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
        if ($hideFunctionality == 'hidden' && !$show) {
            return;
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
            \izi\prestashop\Logger::log('CART = TRUE');
            $style .= (\Configuration::get('INPOST_PAY_margin_cart_left') ? 'margin-left: ' . \Configuration::get('INPOST_PAY_margin_cart_left') . 'px;' : '');
            $style .= (\Configuration::get('INPOST_PAY_margin_cart_right') ? 'margin-right: ' . \Configuration::get('INPOST_PAY_margin_cart_right') . 'px;' : '');
            $style .= (\Configuration::get('INPOST_PAY_margin_cart_up') ? 'margin-top: ' . \Configuration::get('INPOST_PAY_margin_cart_up') . 'px;' : '');
            $style .= (\Configuration::get('INPOST_PAY_margin_cart_down') ? 'margin-bottom: ' . \Configuration::get('INPOST_PAY_margin_cart_down') . 'px;' : '');
        }
        $this->context->smarty->assign(
            [
                'style' => $style,
                'mymodule_izi_html' => $html,
            ]
        );

        return $this->display(__FILE__, 'mymodule.tpl');
    }

    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerJavascript(
            'inpostizi-javascript',
//            $this->_path . 'assets/prestashopizi.js',
            $this->_path . 'assets/prestashopizi.js',
            [
                'position' => 'bottom',
                'priority' => 101,
//                'server' => 'remote'
            ]
        );

        $this->context->controller->registerJavascript(
            'inpostizi.js',
            'https://izi.inpost.pl/inpostizi.js',
            ['position' => 'bottom', 'priority' => 100, 'server' => 'remote']
        );

//       $this->context->controller->registerJavascript(
//           'inzpostizi.js',
//           $this->_path . 'assets/inpostizi.js',
//           ['position' => 'bottom', 'priority' => 100]
//       );
    }

    public function hookActionCartSave($params)
    {
        \izi\InPostIzi::unblockPut();
        \izi\Remote::$done = false;
        \izi\prestashop\Logger::log('SAVING CART');
        $izi = \izi\prestashop\InpostIziPayPrestashop::getInstance();
        \izi\prestashop\CartSession::storeCurrent();
        $izi->basketPut();
    }

    public function hookActionCartUpdateQuantityBefore($params)
    {
        $this->hookActionCartSave($params);
    }

    public function hookDisplayAdminOrderSide($params)
    {
        $orderData = \izi\prestashop\CartSession::getOrderData($params['id_order']);
        if (!$orderData) {
            return;
        }
        $orderData = json_decode($orderData);

        $this->context->smarty->assign(
            [
                'delivery' => $orderData->delivery->delivery_type == 'APM' ? 'Paczkomat' : 'Kurier',
                'apm' => $orderData->delivery->delivery_type == 'APM' ? $orderData->delivery->delivery_point : '',
            ]
        );

        return $this->display(__FILE__, 'backend.tpl');
    }

    public function hookActionPresentCart($params)
    {
        if (
            isset($_POST, $_POST['addDiscount'])
            || (isset($_POST, $_POST['action']) && ($_POST['action'] == 'remove-voucher' || $_POST['action'] == 'add-voucher'))
        ) {
            $izi = \izi\prestashop\InpostIziPayPrestashop::getInstance();
            \izi\prestashop\CartSession::storeCurrent();
            $izi->basketPut();
        }
    }

    public function hookDisplayOrderConfirmation($order)
    {
        \izi\prestashop\InpostIziPayPrestashop::getInstance()->getController()->basketBindingDelete();
        \izi\BasketIdentification::drop();
    }
}

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }
}
