<?php

namespace izi\prestashop\rest\Exception;

final class BasketNotFoundException extends ApiException
{
    public const ERROR_CODE = 'BASKET_NOT_FOUND';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return 404;
    }

    public static function create(): self
    {
        return new self('Basket not found.');
    }
}
