<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

class BadRequestException extends BasketAppException
{
    public const ERROR_CODE = 'BAD_REQUEST';
}
