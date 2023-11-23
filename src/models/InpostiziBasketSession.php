<?php

namespace izi\prestashop\models;

class InpostiziBasketSession extends \ObjectModel
{
    public static $definition = [
        'table' => 'inpostizi_basket_session',
        'primary' => 'id',
        'multilang' => false,
        'fields' => [
            'session_id' => ['type' => self::TYPE_STRING, 'allow_null' => false, 'value' => null],
            'confirmation_response' => ['type' => self::TYPE_STRING, 'allow_null' => true, 'value' => null],
            'cart_id' => ['type' => self::TYPE_STRING, 'allow_null' => false, 'value' => null],
            'order_id' => ['type' => self::TYPE_INT, 'allow_null' => true, 'value' => null],
            'order_details' => ['type' => self::TYPE_STRING, 'allow_null' => true, 'value' => null],
            'redirect_url' => ['type' => self::TYPE_STRING, 'allow_null' => true, 'value' => null],
            'basket_cache' => ['type' => self::TYPE_STRING, 'allow_null' => true, 'value' => null],
            'basket_cached' => ['type' => self::TYPE_STRING, 'allow_null' => true, 'value' => null],
            'coupons' => ['type' => self::TYPE_STRING, 'allow_null' => true, 'value' => null],
            'event' => ['type' => self::TYPE_INT, 'allow_null' => true, 'value' => null],
            'redirected' => ['type' => self::TYPE_BOOL, 'allow_null' => true, 'value' => null],
        ],
    ];
}
