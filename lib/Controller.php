<?php

namespace izi;

class Controller extends Remote
{
    public static $addToCart;

    public function __construct()
    {
        parent::__construct();
    }

    public function basketBindingDelete()
    {
        Storage::insertSession('basketLinked', false);
        $response = parent::basketBindingDelete();
        BasketIdentification::drop();

        return $response;
    }

    public function browserBindingDelete($browserId = null)
    {
        if (!$browserId) {
            $browserId = Storage::findSession('BrowserId');
        }
        $response = '';
        if ($browserId) {
            $response = parent::browserBindingDelete($browserId);
            Storage::eraseSession('BrowserId');
        }

        return $response;
    }

    public function basketBindingGet($force = false)
    {
//        if ($force) {
//            if (method_exists(InPostIzi::getLoggerClass(), 'spam')) {
//                ob_start();
//                debug_print_backtrace(0, 3);
//                $trace = ob_get_contents();
//                ob_end_clean();
//                InPostIzi::getLoggerClass()::spam($trace);
//            }
//        }
        $response = parent::basketBindingGet($force);

        if (isset($response, $this->basketId)) {
            $response->basketId = $this->basketId;
        }

        return $response;
    }

    public function basketBindingPost($prefix = null, $number = null)
    {
        Storage::insertSession('basketLinked', true);
        $binding = $this->basketBindingGet(true);
        if (isset($binding, $binding->browser_trusted) && $binding->browser_trusted) {
            InPostIzi::getLoggerClass()::log('browser is trusted');
//            InPostIzi::getCartSessionClass()::setConfirmationToCart(BasketIdentification::get(), json_encode($binding->client_details));
            $izi = InPostIzi::getInstance();
            Storage::eraseSession('binding_get');
            if (method_exists(InPostIzi::getCartSessionClass(), 'initiateWCCart')) {
                InPostIzi::getCartSessionClass()::initiateWCCart();
            }
            $izi->basketPut(true);
            Storage::eraseSession('binding_get');

            return [];
        }
        InPostIzi::getLoggerClass()::log('browser is not trusted');
        $response = parent::basketBindingPost($prefix, $number);
        if (!$response) {
            $response = new \stdClass();
        }
        $response->basketId = $this->basketId;

        return $response;
    }

    public function basketBindingGetInterval($id = '', $maxTicks = 0)
    {
        $ticks = 0;
        $maxTicks = $maxTicks || 18;
        if (!$id) {
            $id = BasketIdentification::get();
        }
        session_write_close();
        if ($id) {
            while ($ticks < $maxTicks) {
                $data = InPostIzi::getCartSessionClass()::getCartConfirmation($id);
                if (is_string($data) && strlen($data) > 10) {
                    return $data;
                }
                ++$ticks;
                usleep(300000);
            }
        }

        return [];
    }

    private function putBasket()
    {
        if (function_exists('did_action')) {
            if (!did_action('woocommerce_load_cart_from_session') && function_exists('wc_load_cart')) {
                wc_load_cart();
            }
        }
        $izi = InPostIzi::getInstance();
        $izi->basketPut();
    }

    public function orderGetInterval($id = '')
    {
        $ticks = 1;
        if (!$id) {
            $id = Storage::findSession(BasketIdentification::INPOSTIZI_BASKET_ID);
        }

        while ($ticks < 2) {
            $model = InPostIzi::getCartSessionClass()::getObjectById($id);
            if (!$model || !$model->id) {
                usleep(300000);

                return [];
            }
            $redirectUrl = isset($model->confirmation_response) && $model->confirmation_response == 'deleted' ? 'deleted' : (isset($model) ? $model->redirect_url : '');
            if ($redirectUrl && $redirectUrl != 'deleted') {
                if (!InPostIzi::getCartSessionClass()::getRedirectedById($id)) {
                    InPostIzi::getCartSessionClass()::setRedirectedById($id, 1);

                    return [
                        'action' => 'redirect',
                        'redirect' => $redirectUrl,
                    ]; // Removed json_encode
                }
            } elseif ('deleted' === $redirectUrl) {
                return [
                    'action' => 'delete',
                ];
            } elseif ($model->coupons == 1) {
                if (method_exists($model, 'save')) {
                    $model->coupons = 0;
                    $model->save();
                } else {
                    InPostIzi::getCartSessionClass()::setBasketCouponsById($id, 0);
                }
                InPostIzi::getLoggerClass()::log('SENDING REFRESH');

                return [
                    'action' => 'refresh',
                ];
            } elseif (method_exists(InPostIzi::getCartSessionClass(), 'refresh') && InPostIzi::getCartSessionClass()::refresh($id)) {
                return [
                    'action' => 'refresh',
                ];
            }
            ++$ticks;
            usleep(300000);
        }

        return [];
    }

    public function getSignatureKeys($force = false)
    {
        return $this->request('v1/izi/signing-keys/public', 'GET');
    }
}
