<?php

use izi\BasketIdentification;
use izi\BindingProvider;
use izi\InPostIzi;
use izi\prestashop\CartSession;
use izi\prestashop\PrestashopBasket;
use izi\prestashop\rest\SignatureVerification;
use izi\Storage;

class InpostIziBackendModuleFrontController extends ModuleFrontController
{
    const EVENT_TYPE_PROMO_CODES = 'PROMO_CODES';
    const EVENT_TYPE_PRODUCTS_QUANTITY = 'PRODUCTS_QUANTITY';
    const EVENT_TYPE_RELATED_PRODUCTS = 'RELATED_PRODUCTS';

    protected $hasCoupons = false;
    protected $couponError = false;

    public function displayAjax()
    {
        $this->display();
    }

    public function display()
    {
        header('Content-type: application/json');
        \izi\prestashop\Logger::log('request na ' . Tools::getValue('path') . '     FULL: ' . $_SERVER['REQUEST_URI']);

        $originStringPath = urldecode(Tools::getValue('path'));
        $path = explode("/", explode('?', $originStringPath)[0]);

        $confirmationRequest = [];
        preg_match('/inpost\/v1\/izi\/basket\/(.*)\/confirmation/', $originStringPath, $confirmationRequest);

        $getBasketRequest = [];
        preg_match('/inpost\/v1\/izi\/basket\/(.*)/', $originStringPath, $getBasketRequest);

        $deleteBasketRequest = [];
        preg_match('/inpost\/v1\/izi\/basket\/(.*)\/binding/', $originStringPath, $deleteBasketRequest);

        $basketeventRequest = [];
        preg_match('/inpost\/v1\/izi\/basket\/(.*)\/event/', $originStringPath, $basketeventRequest);

        $orderEventRequest = [];
        preg_match('/inpost\/v1\/izi\/order\/(.*)\/event/', $originStringPath, $orderEventRequest);

        if (strpos($originStringPath, 'add-product') === 0) {
            $this->addProductToCart();
        } else if (strpos($originStringPath, 'inpost/v1/izi/merchant/basket/get/link') === 0) {
            \izi\prestashop\InpostIziPayPrestashop::getInstance();
            $binding = BindingProvider::getBinding();

            if (!isset($binding->inpost_basket_id)) {
                return [
                    'link' => '',
                    'inpost_basket_id' => '',
                ];
            }
            $inpost_basket_id = $binding->inpost_basket_id;
            $link =\izi\InPostIzi::getLinkUrl() . '?basket_id=' . $inpost_basket_id;

            die(json_encode([
                'link' => $link,
                'inpost_basket_id' => $inpost_basket_id,
            ]));
        } else if (strpos($originStringPath, 'inpost/v1/izi/merchant/basket/post/binding') === 0) {
            $this->bindCarts($path);
        } else if (strpos($originStringPath, 'inpost/v1/izi/merchant/order/confirmation/get') === 0) {
            \izi\prestashop\InpostIziPayPrestashop::getInstance();
            (new \izi\prestashop\requests\merchant\OrderConfirmation())->send();
        } else if (strpos($originStringPath, 'inpost/v1/izi/merchant/basket/confirmation') === 0) {
            \izi\prestashop\InpostIziPayPrestashop::getInstance();
            (new \izi\prestashop\requests\merchant\BasketConfirmation())->send();
        } else if (strpos($originStringPath, 'inpost/v1/izi/merchant/basket/delete/binding') === 0) {
            $response = \izi\prestashop\InpostIziPayPrestashop::getInstance()->getController()->basketBindingDelete();
            \izi\BasketIdentification::drop();
            $context = \Context::getContext();
            $id_cart = $context->cookie->__get('id_cart');
            $cart = new \Cart($id_cart);
            \izi\InPostIzi::unblockPut();
            $data = PrestashopBasket::getBasket($cart)->encode();
            $basketId = \izi\BasketIdentification::get();
            $jsonResponse = json_encode(json_decode($data));
            CartSession::storeCurrent();
            CartSession::setBasketCacheById($basketId, $jsonResponse);
            \izi\prestashop\Logger::log("Saved {$basketId} for {$jsonResponse}");
            die(json_encode($response));
        } else if ($_SERVER['REQUEST_METHOD'] === "POST" && count($confirmationRequest) == 2) {
            $this->baConfirmation($confirmationRequest[1]);
        } else if ($_SERVER['REQUEST_METHOD'] === "GET" && count($getBasketRequest) == 2) {
            $this->baBasketGet($getBasketRequest[1]);
        }  else if ($_SERVER['REQUEST_METHOD'] === "DELETE" && count($deleteBasketRequest) == 2) {
            \izi\prestashop\InpostIziPayPrestashop::getInstance();
            (new izi\prestashop\requests\basket\Delete())->handleRequest($deleteBasketRequest[1]);
        } else if ($_SERVER['REQUEST_METHOD'] === "POST" && count($basketeventRequest) == 2 && count($orderEventRequest) != 2) {
            $this->cartUpdate($basketeventRequest[1]);
        } else if ($_SERVER['REQUEST_METHOD'] === "POST" && count($orderEventRequest) == 2) {
            $signature = new SignatureVerification();
            $signature->check();
            $id = $orderEventRequest[1];
            $data = file_get_contents('php://input');
            \izi\prestashop\Logger::response($data, 'Order update request came!');
            $date = date("Y-m-d H:i:s");
            $data = json_decode($data);

            \Configuration::get('INPOST_PAY_authorized_payment');
            $order = new Order($id);
            if (!$order) {
                http_response_code(404);
                die(json_encode([
                    'error_code' => '404',
                    'error_message' => 'Order Not Found'
                ]));
            }
            $setToStatus = $order->current_state;
            $statusString = '';
            if ($data->event_data->payment_status == 'AUTHORIZED') {
                $setToStatus = (int)\Configuration::get('INPOST_PAY_authorized_payment');
                $order->setCurrentState($setToStatus);
                $order->save();
                $statusString = 'Opłacono';
            } else {

            }
            \izi\prestashop\Logger::log("STATUS ORDERU {$id} ZMIENIONO NA {$setToStatus}");

            $states = new OrderState();
            foreach ($states->getOrderStates((int)Configuration::get('PS_LANG_DEFAULT')) as $status) {
                if ($status['id_order_state'] == $setToStatus) {
                    $statusString = $status['name'];
                }
            }

            $data = [
                'order_merchant_status_description' => $statusString,
            ];
            die (json_encode($data));
        } else if (strpos($originStringPath, 'inpost/v1/izi/order/') === 0) {
            $signature = new SignatureVerification();
            $signature->check();
            $orderId = array_pop($path);
            $basketId = CartSession::getBasketIdByOrderId($orderId);
            $orderGetResponse = \izi\prestashop\PrestashopOrder::getOrder($orderId, $basketId)->encode();
            \izi\prestashop\Logger::response($orderGetResponse, 'Order get response');
            die ($orderGetResponse);
        } else if (strpos($originStringPath, 'inpost/v1/izi/order') === 0) {
            $signature = new SignatureVerification();
            $signature->check();
            $orderCreateResponse = '';
            try {
                $json = file_get_contents('php://input');
                \izi\prestashop\Logger::response($json, 'Order create came');
                $json = json_decode($json);
                \izi\InPostIzi::blockPut();
                $orderId = (new izi\prestashop\rest\order\Create())->handleRequest($json);
                $orderCreateResponse = \izi\prestashop\PrestashopOrder::getOrder($orderId, $json->order_details->basket_id)->encode();
                \izi\prestashop\Logger::response($orderCreateResponse, 'Order create response');
            } catch (\Throwable $t) {
                \izi\prestashop\Logger::log($t->getMessage() . " at " . $t->getFile() . ":" . $t->getLine());
            }
            \izi\prestashop\InpostIziPayPrestashop::getInstance()->getController()->basketBindingDelete();
            \izi\BasketIdentification::drop();
            die($orderCreateResponse);
        }
//        else if ($path[0] === "bakset" && $_SERVER['REQUEST_METHOD'] === "GET") {
//            die($this->ajaxRender((new izi\prestashop\rest\basket\Get())->handleRequest($path[1])));
//        }
        die(json_encode([
            'access' => 'denied',
            'path' => $originStringPath,
        ]));
    }

    protected function addProductToCart(): void
    {
        $context = \Context::getContext();
        $id_cart = $context->cookie->__get('id_cart');
        $cart = null;
        if ($id_cart) {
            $cart = new \Cart($id_cart);
        } else {
            $cart = new \Cart();
            $cart->id_currency = $context->cookie->id_currency;
            $cart->id_lang = (int)\Configuration::get('PS_LANG_DEFAULT');
            $cart->save();
        }
        $id_product = $_GET['product_id'];
        $quantity = 1;

        $status = $cart->updateQty($quantity, $id_product, null, false);
        die(json_encode(['status' => (int)$status]));
    }

    /**
     * @param $path
     */
    protected function bindCarts($path): void
    {
        \izi\prestashop\InpostIziPayPrestashop::getInstance();
        CartSession::forceBasketStore();
        $browserId = Storage::findSession('BrowserId');
        if (!$browserId && isset($_COOKIE['BrowserId'])) {
            $browserId = $_COOKIE['BrowserId'];
        }
        if ($browserId) {
            \izi\prestashop\Logger::log("MAM BROWSER ID, WYSYŁAM KOSZYK");
            Storage::eraseSession('binding_get');
            $binding = BindingProvider::getBinding(true);
            if ($binding && isset($binding->browser_trusted) && $binding->browser_trusted) {
                \izi\prestashop\Logger::log('pre basket send');
                \izi\InpostIzi::unblockPut();

                $basket = InPostIzi::getCartSessionClass()::getBasketCacheById(BasketIdentification::get());
                if (!$basket) {
                    $izi = \izi\prestashop\InpostIziPayPrestashop::getInstance();
                    \izi\prestashop\CartSession::storeCurrent();
                    $izi->basketPut(false, true);
                }
                $response = \izi\prestashop\InpostIziPayPrestashop::getInstance()->getController()->basketBindingPost();
                $binding = BindingProvider::getBinding(true);
                foreach ($binding->client_details as $innerKey => $innerData) {
                    $binding->$innerKey = $innerData;
                }
                CartSession::setConfirmationToCart(\izi\BasketIdentification::get(), json_encode($binding));
                \izi\prestashop\Logger::log('post basket send and binding is ' . json_encode($binding));
                die(json_encode($response));
            }
        }
        \izi\prestashop\Logger::log("NIE MAM PRZEGLĄDARKI I :(");
        $prefix = $path[7] ?? '-';
        $number = $path[8] ?? '-';
        \izi\prestashop\Logger::log('Prefix to: ' . $prefix);
        \izi\prestashop\Logger::log('Numer telefonu to: ' . $number);
        if ($prefix == '-') {
            $prefix = null;
        }
        if ($number == '-') {
            $number = null;
        }
        $response = \izi\prestashop\InpostIziPayPrestashop::getInstance()->getController()->basketBindingPost($prefix, $number);
        die(json_encode((array)$response));
    }

    protected function merchantConfirmation(): void
    {
        $response = \izi\prestashop\InpostIziPayPrestashop::getInstance()->getController()->basketBindingGetInterval();
        if (!is_string($response)) {
            $response = json_encode($response);
        }
        die($response);
    }

    /**
     * @param $cartId
     */
    protected function baBasketGet($cartId)
    {
        $signature = new SignatureVerification();
        $signature->check();
        $basketJson = \izi\prestashop\CartSession::getBasketCacheById($cartId);
        \izi\prestashop\Logger::log('basket get dla ' . $cartId);
        die($basketJson);
    }

    protected function baConfirmation($cartId): void
    {
        $signature = new SignatureVerification();
        $signature->check();
        \izi\prestashop\Logger::log('confirm przyszedł');
        $basketJson = \izi\prestashop\CartSession::getBasketCacheById($cartId);
        \izi\prestashop\Logger::response($basketJson, 'CONFIRM BASKET ' . $cartId);

        $data = file_get_contents('php://input');
        \izi\prestashop\CartSession::setConfirmationToCart($cartId, $data);
        die($basketJson);
    }

    /**
     * @param $basketeventRequest
     */
    protected function cartUpdate($basketeventRequest): void
    {
        $signature = new SignatureVerification();
        $signature->check();
        $json = file_get_contents('php://input');
        \izi\prestashop\Logger::response($json, 'Basket update request came!');
        $basketId = $basketeventRequest;
        $json = json_decode($json);
        $cartId = CartSession::getSessionId($basketId);
        $cart = new \Cart($cartId);
        if (!$cart->id_currency) {
            $cart->id_currency = \Configuration::get('PS_CURRENCY_DEFAULT');
        }
        if (!$cart->id_lang) {
            $cart->id_lang = \Configuration::get('PS_LANG_DEFAULT');
        }
        switch ($json->event_type) {
            case self::EVENT_TYPE_PRODUCTS_QUANTITY:
                foreach ($json->quantity_event_data as $eventData) {
                    $product_id = explode('.', $eventData->product_id)[0];
                    $variation_id = explode('.', $eventData->product_id)[1];
                    foreach ($cart->getProducts() as $product) {
                        if (
                            $product['id_product'] != $product_id
                            || $product['id_product_attribute'] != $variation_id
                        ) {
                            continue;
                        }
                        if ($eventData->quantity->quantity == 0) {
                            $cart->deleteProduct((int)$product_id, $product['id_product_attribute']);
                            break;
                        } else {
                            try {
                                $currentQty = $this->getCartQuantity($cart, (int)$product_id, $product['id_product_attribute']);
                                $diff = ((int)$eventData->quantity->quantity) - $currentQty;
                                \izi\prestashop\Logger::log("CAME: {$eventData->quantity->quantity}; CURRENT: {$currentQty}; SETTING: {$diff}");
                                $cart->updateQty(abs($diff), (int)$product_id, $product['id_product_attribute'], false, ($diff > 0 ? 'up' : 'down'));
                            } catch (\Exception $e) {
                                \izi\prestashop\Logger::log($e->getMessage());
                            }
                            break;
                        }
                    }
                }
                break;
            case self::EVENT_TYPE_PROMO_CODES:
                $this->hasCoupons = true;
                $appliedCodes = [];
                if(isset($json->promo_codes_event_data)) {
                    PrestashopBasket::$hasCoupons = $this->hasCoupons;
                    foreach ($json->promo_codes_event_data as $eventData) {
                        foreach ($cart->getCartRules() as $rule) {
                            if ($rule['code'] == $eventData->promo_code_value) {
                                $appliedCodes[] = $eventData->promo_code_value;
                                continue 2;
                            }
                        }
                        $cartRule = new \CartRule(\CartRule::getIdByCode($eventData->promo_code_value));
                        if (!$cart->addCartRule($cartRule->id)) {
                            $this->couponError = true;
                            \izi\prestashop\Logger::Log('CART RULE ERROR: ' . $eventData->promo_code_value);
                        } else {
                            izi\prestashop\Logger::Log('CART RULE SUCCESS: ' . $eventData->promo_code_value);
                        }
                        $appliedCodes[] = $eventData->promo_code_value;
                    }
                    foreach ($cart->getCartRules() as $rule) {
                        if (!in_array($rule['code'], $appliedCodes)) {
                            $cart->removeCartRule($rule['id_cart_rule']);
                        }
                    }
                }
                PrestashopBasket::$couponError = $this->couponError;
                break;
            case self::EVENT_TYPE_RELATED_PRODUCTS:
                if (isset($json->related_products_event_data[0], $json->related_products_event_data[0]->product_id)) {
                    $productId = explode('.', $json->related_products_event_data[0]->product_id)[0];
                    $productAttribute = explode('.', $json->related_products_event_data[0]->product_id)[1];
                    $cart->updateQty(1, $productId, $productAttribute);
                }
            break;
        }
        $cart->save();
        $data = PrestashopBasket::getBasket($cart)->encode();

        CartSession::setBasketCacheById($basketId, json_encode(json_decode($data)));
        CartSession::setBasketCouponsById($basketId, 1);
        \izi\prestashop\Logger::response($data, "RESPONSE FOR EVENT");
        die($data);
    }

    private function getCartQuantity($cart, $productId, $attribute) {
        $products = $cart->getProducts(true);

        \izi\prestashop\Logger::log('PRODUCTS:' . print_r($products, true));

        foreach ($products as $product)
        {
            if ($product['id_product'] == $productId && (($attribute && $product['id_product_attribute'] == $attribute) || !$attribute)) {
                return (int)$product['cart_quantity'];
            }
        }
        return 0;
    }
}
