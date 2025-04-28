<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Order;

use izi\prestashop\Common\QuantityType;

/**
 * @template T of (int|float)
 */
final class Quantity implements \JsonSerializable
{
    /**
     * @var int|float
     */
    private $quantity;

    /**
     * @var QuantityType
     */
    private $quantity_type;

    /**
     * @var string|null
     */
    private $quantity_unit;

    /**
     * @param T $quantity
     */
    public function __construct($quantity, QuantityType $quantity_type, ?string $quantity_unit = null)
    {
        $this->quantity = $quantity;
        $this->quantity_type = $quantity_type;
        $this->quantity_unit = $quantity_unit;
    }

    /**
     * @return self<int>
     */
    public static function integer(int $quantity, ?string $quantity_unit = null): self
    {
        return new self($quantity, QuantityType::Integer(), $quantity_unit);
    }

    /**
     * @return self<float>
     */
    public static function decimal(float $quantity, ?string $quantity_unit = null): self
    {
        return new self($quantity, QuantityType::Decimal(), $quantity_unit);
    }

    /**
     * @return T
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getType(): QuantityType
    {
        return $this->quantity_type;
    }

    public function getUnit(): ?string
    {
        return $this->quantity_unit;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
