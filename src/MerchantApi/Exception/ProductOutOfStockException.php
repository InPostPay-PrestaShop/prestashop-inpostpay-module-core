<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Exception;

final class ProductOutOfStockException extends ApiException
{
    public const ERROR_CODE = 'OUT_OF_STOCK';

    /**
     * @var int
     */
    private $availableQuantity;

    public function __construct(int $availableQuantity, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->availableQuantity = $availableQuantity;
    }

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return 409;
    }

    public function getAvailableQuantity(): int
    {
        return $this->availableQuantity;
    }

    public static function create(int $availableQuantity): self
    {
        return new self($availableQuantity, 'Product is out of stock.');
    }
}
