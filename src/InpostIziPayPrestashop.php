<?php

namespace izi\prestashop;

use izi\InPostIzi;

class InpostIziPayPrestashop extends \izi\InPostIzi
{
    public function __construct()
    {
        InPostIzi::setClientSecret(\Configuration::get('INPOST_PAY_client_secret'));
        InPostIzi::setClintId(\Configuration::get('INPOST_PAY_client_id'));
        InPostIzi::setCartSessionClass(CartSession::class);
        InPostIzi::setLoggerClass(Logger::class);
        InPostIzi::setEnvironment(\Configuration::get('INPOST_PAY_environment'));
        $tokenCache = new TokenCache();
        InPostIzi::setTokenCacheObject($tokenCache);
        \izi\Storage::initialize();

        parent::__construct();
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getBasket()
    {
        return PrestashopBasket::getBasket();
    }
}
