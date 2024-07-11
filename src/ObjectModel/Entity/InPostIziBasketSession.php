<?php

namespace izi\prestashop\ObjectModel\Entity;

use izi\prestashop\BasketApp\Basket\Request\Basket;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;

class InPostIziBasketSession extends \ObjectModel
{
    /**
     * @var int \Cart ID
     */
    public $session_id;

    /**
     * @var string basket UUID
     */
    public $cart_id;

    /**
     * @var string|null base64 encoded {@see BindingConfirmation}
     */
    public $confirmation_response;

    /**
     * @var int|null \Order ID
     */
    public $order_id;

    /**
     * @var string|null base64 encoded {@see CreateOrderRequest}
     */
    public $order_details;

    /**
     * @var string|null order confirmation page URL
     */
    public $redirect_url;

    /**
     * @var string|null base64 encoded {@see Basket}
     *
     * @deprecated no longer used, to be removed
     */
    public $basket_cache;

    /**
     * @var bool|null if cart was updated via a Merchant API request
     */
    public $coupons;

    /**
     * @var bool
     */
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
            'coupons' => ['type' => self::TYPE_BOOL, 'allow_null' => true],
            'redirected' => ['type' => self::TYPE_BOOL],
        ],
    ];
}
