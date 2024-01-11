<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Request;

final class Quantity implements \JsonSerializable
{
    /**
     * @var int|float
     */
    private $quantity;

    /**
     * @param int|float $quantity
     */
    public function __construct($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return int|float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
