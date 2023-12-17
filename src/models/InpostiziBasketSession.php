<?php

namespace izi\prestashop\models;

class InpostiziBasketSession extends \ObjectModel
{
    /**
     * @var int \Cart ID
     */
    public $session_id;
    public $cart_id;
    public $confirmation_response;
    public $order_id;
    public $order_details;
    public $redirect_url;
    public $basket_cache;
    public $coupons;
    public $event;
    public $redirected = false;

    public static $definition = [
        'table' => 'inpostizi_basket_session',
        'primary' => 'id',
        'fields' => [
            'session_id' => ['type' => self::TYPE_INT, 'required' => true],
            'cart_id' => ['type' => self::TYPE_STRING, 'required' => true],
            'confirmation_response' => ['type' => self::TYPE_STRING, 'allow_null' => true],
            'order_id' => ['type' => self::TYPE_INT, 'allow_null' => true],
            'order_details' => ['type' => self::TYPE_STRING, 'allow_null' => true],
            'redirect_url' => ['type' => self::TYPE_STRING, 'allow_null' => true],
            'basket_cache' => ['type' => self::TYPE_STRING, 'allow_null' => true],
            'coupons' => ['type' => self::TYPE_STRING, 'allow_null' => true],
            'event' => ['type' => self::TYPE_INT, 'allow_null' => true],
            'redirected' => ['type' => self::TYPE_BOOL],
        ],
    ];
}
