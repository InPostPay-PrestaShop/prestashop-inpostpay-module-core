<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Exception;

final class BadGatewayException extends ApiException
{
    public const ERROR_CODE = 'BAD_GATEWAY';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return 502;
    }

    public static function create(\Throwable $previous, string $message = 'Bad gateway'): self
    {
        return new self($message, 0, $previous);
    }
}
