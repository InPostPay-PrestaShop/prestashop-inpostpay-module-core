<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Exception;

final class InvalidSignatureException extends ApiException
{
    public const ERROR_CODE = 'INVALID_SIGNATURE';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return 401;
    }

    public static function missingHeader(string $name): self
    {
        return new self(\sprintf('Missing header: "%s".', $name));
    }
}
