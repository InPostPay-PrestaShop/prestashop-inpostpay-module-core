<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class UnauthorizedException extends BasketAppException
{
    public const ERROR_CODE = 'UNAUTHORIZED';
}
