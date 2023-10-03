<?php

namespace izi;

class Remote extends Connection
{
    protected $basketId;
    public static $done = false;

    public function __construct($basketId = null)
    {
        if ($basketId === null) {
            $this->basketId = BasketIdentification::get();
            //InPostIzi::getLoggerClass()::log("BASKET ID IN Remote Class {$this->basketId}");
        } else {
            $this->basketId = $basketId;
        }
        parent::__construct();
    }

    public function basketGet()
    {
        return $this->request("v1/izi/basket/{$this->basketId}");
    }

    public function basketPut($data, $raw = false)
    {
        if (self::$done) {
            return;
        }
        self::$done = true;
        $toSend = '';
        if ($raw) {
            $toSend = $data;
        } else {
            $toSend = json_decode($data);
        }
        list($response, $code) = $this->request("v1/izi/basket/{$this->basketId}", "PUT", $toSend, true, $raw);
        InPostIzi::getLoggerClass()::response($data, 'Merchant sends basket put for ' . $this->basketId);
        InPostIzi::getLoggerClass()::response($code, 'Basket app code for basket put');
        InPostIzi::getLoggerClass()::response(json_encode($response), 'Basket app response for basket put');
        return $response;
    }

    public function orderEvent($orderId, $status, $refList)
    {
        $data = [
            'event_id' => time(),
            'event_data_time' => gmdate("Y-m-d\TH:i:s.000\Z"),
            'event_data' => [
                'order_merchant_status_description' => $status,
                'delivery_references_list' => $refList
            ]
        ];
        list($response, $code) = $this->request("v1/izi/order/{$orderId}/event", "POST", $data, true);
        InPostIzi::getLoggerClass()::response(json_encode($data), 'Merchant sends event for ' . $orderId);
        InPostIzi::getLoggerClass()::response($code, 'Basket app code for event');
    }

    public function basketBindingGet($force = false)
    {
        if (!$force && Storage::issetSession('binding_get')) {
            InPostIzi::getLoggerClass()::log("GET BINDING FROM CACHE");
            return json_decode(Storage::findSession('binding_get'));
        }

        $browserId = Storage::findSession('BrowserId');
        if (!$browserId && isset($_COOKIE['BrowserId'])) {
            $browserId = $_COOKIE['BrowserId'];
        }
        $getParam = '';
        if ($browserId) {
            $getParam = '?browser_id=' . $browserId;
        }

        InPostIzi::getLoggerClass()::log("GET BINDING FROM REMOTE");
        $response = $this->request("v1/izi/basket/{$this->basketId}/binding{$getParam}");
        Storage::insertSession('binding_get', json_encode($response));
        return $response;
    }

    public function basketBindingPost($prefix = null, $number = null)
    {
        Storage::eraseSession('binding_get');
        $browser = json_decode(base64_decode($_GET['browser']), true);
        $browserArray = [
            "user_agent" => $browser['user_agent'],
            "description" => $browser['description'],
            "platform" => $browser['platform'],
            "architecture" => $browser['architecture'],
            "data_time" => date("Y-m-d\TH:i:s.000\Z"),
            "location" => "-",
            "customer_ip" => $_SERVER['REMOTE_ADDR'],
            "port" => $_SERVER['SERVER_PORT']
        ];
        if ($prefix && $number) {
            return $this->request("v1/izi/basket/{$this->basketId}/binding", "POST", [
                "binding_method" => "PHONE",
                'binding_place' => ($_GET['binding_place'] == '' ? null : $_GET['binding_place']),
                "phone_number" => [
                    "country_prefix" => "+48",
                    "phone" => $number
                ],
                "browser" => $browserArray
            ]);
        } else {

            return $this->request("v1/izi/basket/{$this->basketId}/binding", "POST", [
                "binding_method" => "DEEP_LINK",
                'binding_place' => $_GET['binding_place'],
                "browser" => $browserArray
            ]);
        }
    }

    public function basketBindingDelete()
    {
        return $this->request("v1/izi/basket/{$this->basketId}/binding", "DELETE");
    }

    public function browserBindingDelete($browserId)
    {
        return $this->request("v1/izi/browser/{$browserId}/binding", "DELETE");
    }

    public function basketConfirmation($data)
    {
        return $this->request("v1/private/izi/basket/binding/{$this->basketId}/confirmation", "POST", $data);
    }

    public function orderPost($data)
    {
        $respanse = $this->request("v1/private/izi/inpostpay-order", "POST", $data);

        $this->orderId = $respanse->order_id;

        return $respanse;
    }

    public function orderGet()
    {
        return $this->request("/inpostpay-order/{$this->orderId}");
    }
}
