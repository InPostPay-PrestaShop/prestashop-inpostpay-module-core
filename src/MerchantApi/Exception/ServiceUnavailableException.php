<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Exception;

final class ServiceUnavailableException extends ApiException
{
    public const ERROR_CODE = 'SERVICE_UNAVAILABLE';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return 503;
    }

    public static function create(\Throwable $previous, string $message = 'Service unavailable'): self
    {
        return new self($message, 0, $previous);
    }
}
