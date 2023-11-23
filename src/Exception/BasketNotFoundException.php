<?php

declare(strict_types=1);

namespace izi\prestashop\Exception;

final class BasketNotFoundException extends ApiException
{
    const ERROR_CODE = 'BASKET_NOT_FOUND';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getResponseCode(): int
    {
        return 404;
    }
}
