<?php

namespace izi\prestashop;

use izi\BasketIdentification;
use izi\prestashop\models\InpostiziBasketSession;

class CartSession
{

    public static function storeCurrent(): void
    {
        $cartId = BasketIdentification::get();
        $sql = 'SELECT id FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where cart_id = "' . $cartId . '"';
        $row = \Db::getInstance()->getRow($sql);
        $model = new InpostiziBasketSession();
        if (isset($row['id']) && $row['id']) {
            Logger::log("MODEL FOUND");
            $model = new InpostiziBasketSession($row['id']);
        } else {
            Logger::log("MODEL NOT FOUND ($cartId)" . print_r($row, true));
            Logger::log("SQL $sql");
            $model->confirmation_response = '';
            $model->order_id = null;
            $model->redirect_url = '';
            $model->basket_cache = '';
            $model->basket_cached = '';
            $model->coupons = '';
            $model->redirected = 0;
            $model->order_details = '';
            $model->event = null;
        }

        $context = \Context::getContext();
        $model->session_id = $context->cookie->__get('id_cart');
        $model->cart_id = $cartId;

        $model->save(true);
    }

    public static function setCartOrderRedirectUrl($cartId, $url)
    {
        $cartId = pSQL($cartId);
        $url = pSQL($url);
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET redirect_url = "' . $url . '" WHERE cart_id = "' . $cartId . '"';
        \Db::getInstance()->execute($sql);
    }

    public static function setOrderData($cartId, $orderId, $orderDetails)
    {
        $orderDetails = base64_encode($orderDetails);
        $orderDetails = pSQL($orderDetails);
        $cartId = pSQL($cartId);
        $orderId = (int) $orderId;
        Logger::log('SAVING ORDER DATA');
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET order_details = "' . $orderDetails . '", order_id = "' . $orderId . '" WHERE cart_id = "' . $cartId . '"';
        \Db::getInstance()->execute($sql);
        Logger::log('ORDER DATA SQL ' . $sql);
    }

    public static function getOrderData($orderId)
    {
        $orderId = (int) $orderId;
        $sql = 'SELECT order_details FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where order_id = "' . $orderId . '"';
        $row = \Db::getInstance()->getRow($sql);
        return isset($row['order_details']) ? base64_decode($row['order_details']) : '';
    }

    public static function getCartOrderRedirectUrl($cartId): ?string
    {
        $cartId = pSQL($cartId);
        $sql = 'SELECT redirect_url FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where cart_id = "' . $cartId . '"';
        $row = \Db::getInstance()->getRow($sql);
        return isset($row['redirect_url']) ? $row['redirect_url'] : null;
    }

    public static function setConfirmationToCart($cartId, $confirmation): void
    {
        $confirmation = pSQL(base64_encode($confirmation));
        $cartId = pSQL($cartId);
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET confirmation_response = "' . $confirmation . '" WHERE cart_id = "' . $cartId . '"';
        \Db::getInstance()->execute($sql);
    }

    public static function getCartConfirmation($cartId): ?string
    {
        $cartId = pSQL($cartId);
        $db = \Db::getInstance(_PS_USE_SQL_SLAVE_);
        $request = 'SELECT `confirmation_response` FROM `' . _DB_PREFIX_ . 'inpostizi_basket_session` WHERE cart_id = "' . $cartId . '";';
        $result = $db->getValue($request, false);
        if ($result) {
            $result = base64_decode($result);
        }
        return $result;
    }

    public static function getRedirectedById($cartId)
    {
        $cartId = pSQL($cartId);
        $sql = 'SELECT redirected FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where cart_id = "' . $cartId . '"';
        $row = \Db::getInstance()->getRow($sql);
        return isset($row['redirected']) ? $row['redirected'] : '';
    }

    public static function getBasketCacheById($cartId)
    {
        $cartId = pSQL($cartId);
        $sql = 'SELECT basket_cache FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where cart_id = "' . $cartId . '"';
        $row = \Db::getInstance()->getRow($sql);
        return isset($row['basket_cache']) ? base64_decode($row['basket_cache']) : '';
    }

    public static function getBasketIdByOrderId($oid)
    {
        $oid = (int) $oid;
        $sql = 'SELECT cartId FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where order_id = "' . $oid . '"';
        $row = \Db::getInstance()->getRow($sql);
        return isset($row['cartId']) ? $row['cartId'] : '';
    }

    public static function setBasketCacheById($cartId, $data)
    {
        $cartId = pSQL($cartId);
        Logger::log('UPDATING BASKET CACHE! FOR ' . $cartId . $data);
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET basket_cache = "' . pSQL(base64_encode($data)) . '" WHERE cart_id = "' . $cartId . '"';
        \Db::getInstance()->execute($sql);
    }

    public static function setRedirectedById($cartId, $redirected)
    {
        $cartId = pSQL($cartId);
        $redirected = (int) $redirected;
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET redirected = "' . $redirected . '" WHERE cart_id = "' . $cartId . '"';
        \Db::getInstance()->execute($sql);
    }

    public static function setBasketCachedById($data) {}

    public static function getSessionId($cartId): ?string
    {
        $cartId = pSQL($cartId);
        $sql = 'SELECT session_id FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where cart_id = "' . $cartId . '"';
        $row = \Db::getInstance()->getRow($sql);
        return isset($row['session_id']) ? $row['session_id'] : '';
    }

    public static function getObjectById($cartId)
    {
        $cartId = pSQL($cartId);
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where cart_id = "' . $cartId . '"';
        $row = \Db::getInstance()->getRow($sql);
        return isset($row['id']) ? (object) $row : '';
    }

    public static function dropCartConfirmation($cartId): void
    {
        $cartId = pSQL($cartId);
        $db = \Db::getInstance(_PS_USE_SQL_SLAVE_);
        $table_name = '`' . _DB_PREFIX_ . 'inpostizi_basket_session`';
        $request = "UPDATE {$table_name} SET confirmation_response=\"\" WHERE cart_id = \"{$cartId}\"";
        $db->execute($request);
    }
    public static function setBasketCouponsById($cartId, $data)
    {
        $cartId = pSQL($cartId);
        if ($data == '0') {
            $data = 'NULL';
        } else {
            $data = pSQL($data);
            $data = "'{$data}'";
        }
        $db = \Db::getInstance(_PS_USE_SQL_SLAVE_);
        $table_name = '`' . _DB_PREFIX_ . 'inpostizi_basket_session`';

        $request = "UPDATE {$table_name} SET coupons= ${data} WHERE cart_id = \"{$cartId}\"";
        $db->execute($request);
        Logger::log("SETTING EVENT {$data} FOR {$cartId} {$request}");
    }

    public static function getBasketCouponsById($cartId): ?string
    {
        $cartId = pSQL($cartId);
        $db = \Db::getInstance(_PS_USE_SQL_SLAVE_);
        $request = 'SELECT `coupons` FROM `' . _DB_PREFIX_ . 'inpostizi_basket_session` WHERE cart_id = "' . $cartId . '";';
        $result = $db->getValue($request, false);
        return $result;
    }

    public static function forceBasketStore()
    {
        $basket = PrestashopBasket::getBasket()->encode();
        CartSession::setBasketCacheById(BasketIdentification::get(), json_encode(json_decode($basket)));
    }

    public static function deleteByCartId($cartId): void
    {
        $cartId = pSQL($cartId);
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET redirect_url = "deleted" WHERE cart_id = "' . $cartId . '"';
        \Db::getInstance()->execute($sql);
    }
}
