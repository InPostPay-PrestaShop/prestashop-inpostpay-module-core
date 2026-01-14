<?php

declare(strict_types=1);

namespace izi\prestashop\Cart\Exception;

final class ProductAlreadyInCartException extends \RuntimeException
{
    /**
     * @var array cart product
     */
    private $product;

    public function __construct(array $product, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->product = $product;
    }

    public function getProduct(): array
    {
        return $this->product;
    }
}
