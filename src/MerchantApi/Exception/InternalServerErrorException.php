<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Exception;

final class InternalServerErrorException extends ApiException
{
    public const ERROR_CODE = 'INTERNAL_SERVER_ERROR';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return 500;
    }

    public static function create(\Throwable $previous): self
    {
        return new self('Something went wrong. Please try again later.', 0, $previous);
    }
}
