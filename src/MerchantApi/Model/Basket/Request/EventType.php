<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Request;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self ProductsQuantity()
 * @method static self PromoCodes()
 * @method static self RelatedProducts()
 */
final class EventType extends StringEnum
{
    private const PRODUCTS_QUANTITY = 'PRODUCTS_QUANTITY';
    private const PROMO_CODES = 'PROMO_CODES';
    private const RELATED_PRODUCTS = 'RELATED_PRODUCTS';
}
