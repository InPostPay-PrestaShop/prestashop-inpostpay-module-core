<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class BasketNotBoundException extends BasketAppException
{
    public const ERROR_CODE = 'BASKET_NOT_BOUND';
}
