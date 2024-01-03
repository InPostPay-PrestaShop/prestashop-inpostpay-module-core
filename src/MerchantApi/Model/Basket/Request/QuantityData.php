<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Request;

use izi\prestashop\Common\Basket\Quantity;

final class QuantityData implements \JsonSerializable
{
    /**
     * @var string
     */
    private $product_id;

    /**
     * @var Quantity
     */
    private $quantity;

    public function __construct(string $product_id, Quantity $quantity)
    {
        $this->product_id = $product_id;
        $this->quantity = $quantity;
    }

    public function getProductId(): string
    {
        return $this->product_id;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
