<?php

namespace izi\prestashop\rest\Exception;

final class MalformedRequestException extends ApiException
{
    public const ERROR_CODE = 'MALFORMED_REQUEST';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return 400;
    }

    public static function create(): self
    {
        return new self('Malformed request');
    }
}
