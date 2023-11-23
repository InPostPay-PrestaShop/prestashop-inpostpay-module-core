<?php

namespace izi;

class InPostIzi
{
    const ENVIRONMENT_DEVELOP = 1;
    const ENVIRONMENT_PRODUCTION = 2;
    const ENVIRONMENT_SANDBOX = 3;

    const BINDING_PLACE_PRODUCT_CARD = 'PRODUCT_CARD';
    const BINDING_PLACE_BASKET_SUMMARY = 'BASKET_SUMMARY';
    const BINDING_PLACE_ORDER_CREATE = 'ORDER_CREATE';
    const BINDING_PLACE_BASKET_POPUP = 'BASKET_POPUP';
    const BINDING_PLACE_THANK_YOU_PAGE = 'THANK_YOU_PAGE';

    protected $controller;
    protected static $instance;
    private static $blockPut = false;

    private static $clientId;
    private static $clientSecret;

    private static $environment;

    private static $cartSessionClass;
    private static $loggerClass;

    private static $tokenCache = null;

    public function __construct()
    {
        $this->controller = new Controller();
    }

    public function getController()
    {
        return $this->controller;
    }

    public static function setCartSessionClass($class)
    {
        self::$cartSessionClass = $class;
    }

    public static function getCartSessionClass()
    {
        return self::$cartSessionClass;
    }

    public static function setLoggerClass($class)
    {
        self::$loggerClass = $class;
    }

    public static function getLoggerClass()
    {
        return self::$loggerClass;
    }

    public static function setEnvironment($environment)
    {
        self::$environment = $environment;
    }

    public static function getApiUrl()
    {
        switch (self::$environment) {
            case self::ENVIRONMENT_PRODUCTION:
                return 'https://api.inpost.pl';
            case self::ENVIRONMENT_SANDBOX:
                return 'https://sandbox-api.inpost.pl';
            default:
                return 'https://uat-api.inpost.pl';
        }
    }

    public static function getAuthUrl()
    {
        switch (self::$environment) {
            case self::ENVIRONMENT_PRODUCTION:
                return 'https://login.inpost.pl';
            case self::ENVIRONMENT_SANDBOX:
                return 'https://sandbox-login.inpost.pl';
            default:
                return 'https://uat-auth.easypack24.net';
        }
    }

    public static function getLinkUrl()
    {
        switch (self::$environment) {
            case self::ENVIRONMENT_PRODUCTION:
                return 'inpost://izilink';
            case self::ENVIRONMENT_SANDBOX:
                return 'inpost://izilinksandbox';
            default:
                return 'inpost://izilinkuat';
        }
    }

    public static function getJsUrl()
    {
        switch (self::$environment) {
            case self::ENVIRONMENT_PRODUCTION:
                return 'https://izi.inpost.pl/inpostizi.js';
            case self::ENVIRONMENT_SANDBOX:
                return 'https://izi-sandbox.inpost.pl/inpostizi.js';
            default:
                return 'https://izi-uat.inpost.pl/inpostizi.js';
        }
    }

    public static function blockPut()
    {
        //        ob_start();
        //        debug_print_backtrace(0, 1);
        //        $trace = ob_get_contents();
        //        ob_end_clean();
        //        self::$loggerClass::log($trace);
        self::$blockPut = true;
    }

    public static function unblockPut()
    {
        self::$blockPut = false;
    }

    public function orderEvent($orderId, $status, $refList)
    {
        $this->controller->orderEvent($orderId, $status, $refList);
    }

    public function basketPut($forceUnbound = false, $justStore = false)
    {
        self::$loggerClass::Log('PERFORMING PUT WITH PARAMETERS: $forceUnbound = ' . (int) $forceUnbound . ', $justStore = ' . (int) $justStore . ' self::$blockPut = ' . (int) self::$blockPut);
        if (!self::$blockPut) {
            $data = $this->getBasket()->encode();

            self::getCartSessionClass()::setBasketCacheById(BasketIdentification::get(), $data);
            self::getCartSessionClass()::setBasketCachedById(BasketIdentification::get());

            if ($justStore) {
                return;
            }

            $binding = BindingProvider::getBinding(); //!! removed true
            $basketLinkedForLog = false;
            if (isset($binding->basket_linked)) {
                $basketLinkedForLog = $binding->basket_linked;
            }

            if (!$forceUnbound && (!$binding || !$basketLinkedForLog)) {
                $forceUnbound = print_r((int) $forceUnbound, true);
                $basketLinkedForLog = print_r((int) $basketLinkedForLog, true);
                self::$loggerClass::response('', "NO put: forceUnbound:{$forceUnbound} binding->basket_linked:{$basketLinkedForLog}");

                return;
            }

            self::$loggerClass::response('', "Performing put: forceUnbound:{$forceUnbound} binding->basket_linked:{$basketLinkedForLog}");

            $basket = InPostIzi::getCartSessionClass()::getBasketCacheById(BasketIdentification::get());
            $basket = str_replace('\/', '/', mb_convert_encoding($basket, 'UTF-8'));
            self::getCartSessionClass()::setBasketCacheById(BasketIdentification::get(), $basket);
            $this->controller->basketPut($basket, true);
        } else {
            InPostIzi::getLoggerClass()::log('Block PUT');
        }
    }

    public static function setTokenCacheObject($object)
    {
        self::$tokenCache = $object;
    }

    public static function getCachedToken()
    {
        if (self::$tokenCache) {
            return self::$tokenCache->getCachedToken();
        }

        return null;
    }

    public static function setCachedToken($token, $expiration)
    {
        if (self::$tokenCache) {
            return self::$tokenCache->setCachedToken($token, $expiration);
        }
    }

    public static function print()
    {
        echo '<inpost-izi-button language="pl"></inpost-izi-button>';
    }

    public function sendOrder()
    {
        $basket = $this->getBasket();
        $orderRespanse = $this->controller->orderPost($this->getOrder()->toArray());

        Storage::insertSession('sameBasket', $basket->compareProduct($orderRespanse->products));
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @param $clientId string
     */
    public static function setClintId($clientId)
    {
        self::$clientId = $clientId;
    }

    public static function getClientId()
    {
        return self::$clientId;
    }

    public static function setClientSecret($clientSecret)
    {
        self::$clientSecret = $clientSecret;
    }

    public static function getClientSecret()
    {
        return self::$clientSecret;
    }

    public static function render(
        $productId = null,
        $name = '',
        $maskedPhoneNumber = '',
        $inpost_basket_id = '',
        $echo = true,
        $addBasketId = false,
        $variationId = '',
        $count = 0,
        $dark = false,
        $yellow = false,
        $cart = false,
        $float = 'left',
        $bindingPlace = 'BASKET_POPUP'
    ) {
        $basketId = $addBasketId ? 'basket-id="' . \izi\BasketIdentification::get() . '"' : '';
        $id = BasketIdentification::get();
        $html = '';
        $variationHtml = '';

        if ($variationId) {
            $variationHtml = 'variationId="' . $variationId . '"';
        }

        $data = self::getCartSessionClass()::getCartOrderRedirectUrl($id);
        if ($data != null) {
            BasketIdentification::drop();
            $id = BasketIdentification::get();
        }

        $cartConfirmation = self::getCartSessionClass()::getCartConfirmation($id);
        if (!$cartConfirmation) {
            $maskedPhoneNumber = '';
        }

        $count = "count=\"{$count}\"";

        $dark = $dark ? ' dark_mode="true" ' : '';
        $yellow = $yellow ? ' variant="primary" ' : ' variant="secondary" ';
        $cart = $cart ? ' basket="true" ' : '';

        $float = 'class="float-' . $float . '"';

        $bindingPlace = " binding_place=\"{$bindingPlace}\" ";

        if ($productId) {
            $html = '<inpost-izi-button ' . $bindingPlace . $float . $cart . $dark . $yellow . $count . $inpost_basket_id . ' ' . $variationHtml . ' name="' . $name . '" masked_phone_number="' . $maskedPhoneNumber . '" data-product-id="' . $productId . '" language="pl" ' . $basketId . '></inpost-izi-button>';
        } else {
            $html = '<inpost-izi-button ' . $bindingPlace . $float . $cart . $dark . $yellow . $count . $inpost_basket_id . ' ' . $variationHtml . ' name="' . $name . '" masked_phone_number="' . $maskedPhoneNumber . '" language="pl" ' . $basketId . '></inpost-izi-button>';
        }

        //        $html = "<!-- mfunc mysecurestring --><!--esi \n {$html} \n --><!-- /mfunc mysecurestring -->";

        if ($echo) {
            echo $html;
        }

        return $html;
    }
}
