<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class OrderNotFoundException extends ResourceNotFoundException
{
    public const ERROR_CODE = 'ORDER_NOT_FOUND';
}
