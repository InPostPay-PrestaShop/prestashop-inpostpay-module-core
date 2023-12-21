<?php

namespace izi;

use izi\item\Basket;

class Remote extends Connection
{
    protected $basketId;

    public function __construct($basketId = null)
    {
        if ($basketId === null) {
            $this->basketId = BasketIdentification::get();
        } else {
            $this->basketId = $basketId;
        }
        parent::__construct();
    }

    public function basketGet()
    {
        return $this->request("v1/izi/basket/{$this->basketId}");
    }

    public function basketPut(Basket $basket)
    {
        list($response, $code) = $this->request("v1/izi/basket/{$basket->getId()}", 'PUT', $basket, true);
        InPostIzi::getLoggerClass()::response('Merchant sends basket put for ' . $basket->getId());
        InPostIzi::getLoggerClass()::response($code, 'Basket app code for basket put');
        InPostIzi::getLoggerClass()::response(json_encode($response), 'Basket app response for basket put');

        return $response;
    }

    /**
     * @param string|int $orderId
     */
    public function orderEvent($orderId, string $status, array $refList)
    {
        $data = [
            'event_id' => time(),
            'event_data_time' => gmdate("Y-m-d\TH:i:s.000\Z"),
            'event_data' => [
                'order_merchant_status_description' => $status,
                'delivery_references_list' => $refList,
            ],
        ];
        list($response, $code) = $this->request("v1/izi/order/{$orderId}/event", 'POST', $data, true);
        InPostIzi::getLoggerClass()::response(json_encode($data), 'Merchant sends event for ' . $orderId);
        InPostIzi::getLoggerClass()::response($code, 'Basket app code for event');
    }

    public function basketBindingGet($force = false)
    {
        if (!$force && Storage::issetSession('binding_get')) {
            InPostIzi::getLoggerClass()::log('GET BINDING FROM CACHE');

            return json_decode(Storage::findSession('binding_get'));
        }

        $browserId = Storage::findSession('BrowserId');
        if (!$browserId && isset($_COOKIE['BrowserId'])) {
            $browserId = $_COOKIE['BrowserId'];
        }

        InPostIzi::getLoggerClass()::log('GET BINDING FROM REMOTE');
        $response = $this->getBasketBinding($this->basketId, $browserId);
        Storage::insertSession('binding_get', json_encode($response));

        return $response;
    }

    public function getBasketBinding(string $basketId, string $browserId = null)
    {
        $query = $browserId ? '?browser_id=' . $browserId : '';

        return $this->request("v1/izi/basket/{$basketId}/binding{$query}");
    }

    public function basketBindingPost($prefix = null, $number = null)
    {
        Storage::eraseSession('binding_get');
        $browser = json_decode(base64_decode($_GET['browser']), true);
        $browserArray = [
            'user_agent' => $browser['user_agent'],
            'description' => $browser['description'],
            'platform' => $browser['platform'],
            'architecture' => $browser['architecture'],
            'data_time' => date("Y-m-d\TH:i:s.000\Z"),
            'location' => '-',
            'customer_ip' => $_SERVER['REMOTE_ADDR'],
            'port' => $_SERVER['SERVER_PORT'],
        ];
        if ($prefix && $number) {
            return $this->request("v1/izi/basket/{$this->basketId}/binding", 'POST', [
                'binding_method' => 'PHONE',
                'binding_place' => ($_GET['binding_place'] == '' ? null : $_GET['binding_place']),
                'phone_number' => [
                    'country_prefix' => '+' . ltrim($prefix, '+'),
                    'phone' => $number,
                ],
                'browser' => $browserArray,
            ]);
        } else {
            return $this->request("v1/izi/basket/{$this->basketId}/binding", 'POST', [
                'binding_method' => 'DEEP_LINK',
                'binding_place' => $_GET['binding_place'],
                'browser' => $browserArray,
            ]);
        }
    }

    public function basketBindingDelete(string $basketId, bool $ordered = false)
    {
        $query = $ordered ? '?if_basket_realized=1' : '';

        return $this->request("v1/izi/basket/{$basketId}/binding{$query}", 'DELETE');
    }

    public function browserBindingDelete($browserId)
    {
        return $this->request("v1/izi/browser/{$browserId}/binding", 'DELETE');
    }
}
