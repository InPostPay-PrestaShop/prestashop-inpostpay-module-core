<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class ForbiddenException extends BasketAppException
{
    public const ERROR_CODE = 'FORBIDDEN';
}
