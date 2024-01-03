<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class BasketAlreadyBoundException extends BasketAppException
{
    public const ERROR_CODE = 'BASKET_IS_BINDED';
}
