<?php

declare(strict_types=1);

namespace izi\prestashop\Common\HotProduct;

use izi\prestashop\Common\QuantityType;

/**
 * @template T of (int|float)
 */
final class Quantity implements \JsonSerializable
{
    /**
     * @var int|float
     */
    private $available_quantity;

    /**
     * @var QuantityType
     */
    private $quantity_type;

    /**
     * @var string|null
     */
    private $quantity_unit;

    /**
     * @param T $available_quantity
     */
    public function __construct($available_quantity, QuantityType $quantity_type, ?string $quantity_unit = null)
    {
        $this->available_quantity = $available_quantity;
        $this->quantity_type = $quantity_type;
        $this->quantity_unit = $quantity_unit;
    }

    /**
     * @return static<int>
     */
    public static function integer(int $available_quantity, ?string $quantity_unit = null): self
    {
        return new static($available_quantity, QuantityType::Integer(), $quantity_unit);
    }

    /**
     * @return static<float>
     */
    public static function decimal(float $available_quantity, ?string $quantity_unit = null): self
    {
        return new static($available_quantity, QuantityType::Decimal(), $quantity_unit);
    }

    /**
     * @return T
     */
    public function getAvailableQuantity()
    {
        return $this->available_quantity;
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
