<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Order\Request;

final class InPostDiscount implements \JsonSerializable
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var float
     */
    private $discount;

    public function __construct(string $type, float $discount)
    {
        $this->type = $type;
        $this->discount = $discount;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return float gross amount
     */
    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
