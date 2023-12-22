<?php

namespace izi\prestashop\Common;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self ProductCard()
 * @method static self BasketSummary()
 * @method static self BasketPopup()
 * @method static self OrderCreate()
 * @method static self ThankYouPage()
 */
final class BindingPlace extends StringEnum
{
    private const PRODUCT_CARD = 'PRODUCT_CARD';
    private const BASKET_SUMMARY = 'BASKET_SUMMARY';
    private const BASKET_POPUP = 'BASKET_POPUP';
    private const ORDER_CREATE = 'ORDER_CREATE';
    private const THANK_YOU_PAGE = 'THANK_YOU_PAGE';
}
