<?php

namespace izi\prestashop;

use izi\BasketIdentification;
use izi\interfaces\ICartSession;
use izi\prestashop\models\InpostiziBasketSession;

class CartSession implements ICartSession
{
    public static function storeCurrent(): void
    {
        $cart = \Context::getContext()->cart;

        if (!\Validate::isLoadedObject($cart)) {
            throw new \RuntimeException('Cart does not exist.');
        }

        self::store($cart);
    }

    public static function setRedirectUrl(string $basketId, string $url)
    {
        $basketId = pSQL($basketId);
        $url = pSQL($url);
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET redirect_url = "' . $url . '" WHERE cart_id = "' . $basketId . '"';
        \Db::getInstance()->execute($sql);
    }

    public static function setOrderData(string $basketId, int $orderId, $orderDetails)
    {
        $orderDetails = base64_encode($orderDetails);
        $orderDetails = pSQL($orderDetails);
        $basketId = pSQL($basketId);
        Logger::log('SAVING ORDER DATA');
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET order_details = "' . $orderDetails . '", order_id = "' . $orderId . '" WHERE cart_id = "' . $basketId . '"';
        \Db::getInstance()->execute($sql);
    }

    public static function getOrderData(int $orderId)
    {
        $sql = 'SELECT order_details FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where order_id = "' . $orderId . '"';
        $row = \Db::getInstance()->getRow($sql);

        return isset($row['order_details']) ? base64_decode($row['order_details']) : '';
    }

    public static function getCartOrderRedirectUrl(string $basketId): ?string
    {
        $basketId = pSQL($basketId);
        $sql = 'SELECT redirect_url FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where cart_id = "' . $basketId . '"';
        $row = \Db::getInstance()->getRow($sql);

        return $row['redirect_url'] ?? null;
    }

    public static function setConfirmationToCart(string $basketId, $confirmation): void
    {
        $confirmation = pSQL(base64_encode($confirmation));
        $basketId = pSQL($basketId);
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET confirmation_response = "' . $confirmation . '" WHERE cart_id = "' . $basketId . '"';
        \Db::getInstance()->execute($sql);
    }

    public static function getCartConfirmation(string $basketId): ?string
    {
        $basketId = pSQL($basketId);
        $db = \Db::getInstance();
        $request = 'SELECT `confirmation_response` FROM `' . _DB_PREFIX_ . 'inpostizi_basket_session` WHERE cart_id = "' . $basketId . '";';
        $result = $db->getValue($request, false);

        return $result ? base64_decode($result) : null;
    }

    public static function getRedirectedById(string $basketId): bool
    {
        $basketId = pSQL($basketId);
        $sql = 'SELECT redirected FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where cart_id = "' . $basketId . '"';
        $row = \Db::getInstance()->getRow($sql);

        return isset($row['redirected']) && $row['redirected'];
    }

    public static function getBasketCacheById(string $basketId)
    {
        $basketId = pSQL($basketId);
        $sql = 'SELECT basket_cache FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where cart_id = "' . $basketId . '"';
        $row = \Db::getInstance()->getRow($sql);

        return isset($row['basket_cache']) ? base64_decode($row['basket_cache']) : '';
    }

    public static function setBasketCacheById(string $basketId, $data)
    {
        $basketId = pSQL($basketId);
        Logger::log('UPDATING BASKET CACHE! FOR ' . $basketId . $data);
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET basket_cache = "' . pSQL(base64_encode($data)) . '" WHERE cart_id = "' . $basketId . '"';
        \Db::getInstance()->execute($sql);
    }

    public static function setRedirectedById(string $basketId, bool $redirected)
    {
        $basketId = pSQL($basketId);
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET redirected = "' . (int) $redirected . '" WHERE cart_id = "' . $basketId . '"';
        \Db::getInstance()->execute($sql);
    }

    public static function getCartIdByBasketId(string $basketId): ?int
    {
        $basketId = pSQL($basketId);
        $sql = 'SELECT session_id FROM ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' where cart_id = "' . $basketId . '"';
        $row = \Db::getInstance()->getRow($sql);

        return isset($row['session_id']) ? (int) $row['session_id'] : null;
    }

    /**
     * @return InpostiziBasketSession|null
     */
    public static function getByBasketId(string $basketId)
    {
        /** @var InpostiziBasketSession|false $session */
        $session = (new \PrestaShopCollection(InpostiziBasketSession::class))
            ->where('cart_id', '=', $basketId)
            ->getFirst();

        return false === $session ? null : $session;
    }

    public static function dropCartConfirmation(string $basketId): void
    {
        $basketId = pSQL($basketId);
        $db = \Db::getInstance();
        $table_name = '`' . _DB_PREFIX_ . 'inpostizi_basket_session`';
        $request = "UPDATE {$table_name} SET confirmation_response = NULL WHERE cart_id = \"{$basketId}\"";
        $db->execute($request);
    }

    public static function setBasketCouponsById(string $basketId, $data)
    {
        $basketId = pSQL($basketId);
        if ($data == '0') {
            $data = 'NULL';
        } else {
            $data = pSQL($data);
            $data = "'{$data}'";
        }
        $db = \Db::getInstance();
        $table_name = '`' . _DB_PREFIX_ . 'inpostizi_basket_session`';

        Logger::log("SETTING EVENT {$data} FOR {$basketId}");

        $request = "UPDATE {$table_name} SET coupons= ${data} WHERE cart_id = \"{$basketId}\"";
        $db->execute($request);
    }

    public static function forceBasketStore()
    {
        $basket = PrestashopBasket::createForCustomerContext();

        CartSession::setBasketCacheById($basket->getId(), json_encode($basket));
    }

    public static function deleteByBasketId(string $basketId): void
    {
        $basketId = pSQL($basketId);
        $sql = 'UPDATE ' . _DB_PREFIX_ . InpostiziBasketSession::$definition['table'] . ' SET redirect_url = "deleted" WHERE cart_id = "' . $basketId . '"';
        \Db::getInstance()->execute($sql);
    }

    public static function getByCartId(int $cartId)
    {
        if (0 >= $cartId) {
            return null;
        }

        /** @var InpostiziBasketSession|false $session */
        $session = (new \PrestaShopCollection(InpostiziBasketSession::class))
            ->where('session_id', '=', $cartId)
            ->getFirst();

        return false === $session ? null : $session;
    }

    public static function getBasketIdByCartId(int $cartId): ?string
    {
        if (null === $session = self::getByCartId($cartId)) {
            return null;
        }

        return $session->cart_id;
    }

    public static function getCurrentBasketId(): ?string
    {
        $context = \Context::getContext();

        if (!$context->controller instanceof \FrontControllerCore) {
            return null;
        }

        if (!\Validate::isLoadedObject($context->cart)) {
            return null;
        }

        return self::getBasketIdByCartId($context->cart->id);
    }

    private static function store(\Cart $cart)
    {
        if (null === $session = self::getByCartId($cart->id)) {
            self::createNewSession($cart);
        } elseif (!BasketIdentification::exists() || BasketIdentification::get() !== $session->cart_id) {
            BasketIdentification::store($session->cart_id);
        }
    }

    private static function createNewSession(\Cart $cart): void
    {
        $model = new InpostiziBasketSession();

        $model->session_id = $cart->id;
        $model->cart_id = BasketIdentification::get();

        try {
            $result = $model->save(true);
        } catch (\PrestaShopDatabaseException $exception) {
            $result = false;
        }

        if (true === $result) {
            return;
        }

        $db = \Db::getInstance();

        if (!in_array($db->getNumberError(), [1062, 1557, 1569, 1586], true)) {
            throw $exception ?? new \PrestaShopDatabaseException($db->getMsgError());
        }

        BasketIdentification::drop();
        self::store($cart);
    }
}
