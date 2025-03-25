<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Exception;

class CannotAddProductException extends ApiException
{
    public const ERROR_CODE = 'PRODUCT_NOT_ADDED';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return 409;
    }

    public static function create(string $reason): self
    {
        return new self($reason);
    }
}
