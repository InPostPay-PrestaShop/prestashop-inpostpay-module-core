<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Exception;

final class OrderExistsException extends ApiException
{
    public const ERROR_CODE = 'ORDER_EXISTS';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return 409;
    }

    public static function create(): self
    {
        return new self('Order already exists for the basket.');
    }
}
