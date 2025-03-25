<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Product\Exception;

use izi\prestashop\BasketApp\Exception\BasketAppException;

final class ProductNotFoundException extends BasketAppException
{
    public const ERROR_CODE = 'PRODUCT_NOT_FOUND';
}
