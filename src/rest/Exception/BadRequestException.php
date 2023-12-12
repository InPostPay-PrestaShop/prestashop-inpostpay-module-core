<?php

namespace izi\prestashop\rest\Exception;

final class BadRequestException extends ApiException
{
    public const ERROR_CODE = 'BAD_REQUEST';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return 400;
    }

    public static function malformedRequest(): self
    {
        return new self('Malformed request');
    }
}
