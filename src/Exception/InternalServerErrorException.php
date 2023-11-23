<?php

declare(strict_types=1);

namespace izi\prestashop\Exception;

final class InternalServerErrorException extends ApiException
{
    const ERROR_CODE = 'INTERNAL_SERVER_ERROR';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getResponseCode(): int
    {
        return 500;
    }
}
