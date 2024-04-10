<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self ProductCard()
 * @method static self BasketSummary()
 * @method static self BasketPopup()
 * @method static self OrderCreate()
 * @method static self ThankYouPage()
 * @method static self LoginPage()
 * @method static self RegisterFormPage()
 * @method static self CheckoutPage()
 * @method static self MiniCartPage()
 */
final class BindingPlace extends StringEnum
{
    private const PRODUCT_CARD = 'PRODUCT_CARD';
    private const BASKET_SUMMARY = 'BASKET_SUMMARY';
    private const BASKET_POPUP = 'BASKET_POPUP';
    private const ORDER_CREATE = 'ORDER_CREATE';
    private const THANK_YOU_PAGE = 'THANK_YOU_PAGE';
    private const LOGIN_PAGE = 'LOGIN_PAGE';
    private const REGISTER_FORM_PAGE = 'REGISTERFORM_PAGE';
    private const CHECKOUT_PAGE = 'CHECKOUT_PAGE';
    private const MINI_CART_PAGE = 'MINICART_PAGE';
}
