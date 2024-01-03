<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class BasketNotFoundException extends ResourceNotFoundException
{
    public const ERROR_CODE = 'BASKET_NOT_FOUND';
}
