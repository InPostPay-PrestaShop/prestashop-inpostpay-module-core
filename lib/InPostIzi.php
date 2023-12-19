<?php

namespace izi;

use izi\interfaces\ICartSession;
use izi\interfaces\LoggerInterface;
use izi\interfaces\TokenCacheInterface;
use izi\item\Basket;

abstract class InPostIzi
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

    /**
     * @var class-string<ICartSession>|ICartSession
     */
    private static $cartSessionClass;

    /**
     * @var class-string<LoggerInterface>|LoggerInterface
     */
    private static $loggerClass;

    /**
     * @var TokenCacheInterface|null
     */
    private static $tokenCache;

    public function __construct()
    {
        $this->controller = new Controller();
    }

    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param class-string<ICartSession> $class
     */
    public static function setCartSessionClass($class)
    {
        self::$cartSessionClass = $class;
    }

    /**
     * @return class-string<ICartSession>|ICartSession
     */
    public static function getCartSessionClass()
    {
        return self::$cartSessionClass;
    }

    /**
     * @param class-string<LoggerInterface> $class
     */
    public static function setLoggerClass($class)
    {
        self::$loggerClass = $class;
    }

    /**
     * @return class-string<LoggerInterface>|LoggerInterface
     */
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

    public function basketPut(bool $forceUnbound = false, bool $justStore = false, Basket $basket = null)
    {
        self::$loggerClass::log('PERFORMING PUT WITH PARAMETERS: $forceUnbound = ' . (int) $forceUnbound . ', $justStore = ' . (int) $justStore . ' self::$blockPut = ' . (int) self::$blockPut);

        if (self::$blockPut) {
            return;
        }

        if (null === $basket) {
            $basket = $this->getBasket();
        }

        $data = str_replace('\/', '/', mb_convert_encoding($basket->encode(), 'UTF-8'));

        self::getCartSessionClass()::setBasketCacheById($basket->getId(), $data);

        if ($justStore) {
            return;
        }

        if (3 < func_num_args()) {
            $binding = BindingProvider::getBinding();
            $basketLinked = $binding->basket_linked ?? false;
        } else {
            $basketLinked = $this->isLinked($basket->getId());
        }

        if (!$forceUnbound && !$basketLinked) {
            self::$loggerClass::response('NO put: forceUnbound:0 binding->basket_linked:0');

            return;
        }

        self::$loggerClass::response(sprintf('Performing put: forceUnbound:%d binding->basket_linked:%d', (int) $forceUnbound, (int) $basketLinked));

        $this->controller->basketPut($basket);
    }

    public static function setTokenCacheObject(TokenCacheInterface $object = null)
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
            self::$tokenCache->setCachedToken($token ?: null, $expiration ?: null);
        }
    }

    public static function print()
    {
        echo '<inpost-izi-button language="pl"></inpost-izi-button>';
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

    abstract public function getBasket(): Basket;

    private function isLinked(string $basketId): bool
    {
        $confirmation = self::getCartSessionClass()::getCartConfirmation($basketId);
        if (!$confirmation) {
            return false;
        }

        $confirmation = json_decode($confirmation, false);

        return ($confirmation->basket_linked ?? false) || 'SUCCESS' === ($confirmation->status ?? null);
    }
}
