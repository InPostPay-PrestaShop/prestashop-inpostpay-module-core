<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

use izi\prestashop\Common\QuantityType;

/**
 * @template T
 */
final class Quantity implements \JsonSerializable
{
    /**
     * @var T
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
     * @var T|null
     */
    private $available_quantity;

    /**
     * @var T|null
     */
    private $max_quantity;

    private function __construct($quantity, QuantityType $quantity_type, string $quantity_unit = null, $available_quantity = null, $max_quantity = null)
    {
        $this->quantity = $quantity;
        $this->quantity_type = $quantity_type;
        $this->quantity_unit = $quantity_unit;
        $this->available_quantity = $available_quantity;
        $this->max_quantity = $max_quantity;
    }

    /**
     * @return self<int>
     */
    public static function integer(int $quantity, int $available_quantity = null, int $max_quantity = null, string $quantity_unit = null): self
    {
        return new self($quantity, QuantityType::Integer(), $quantity_unit, $available_quantity, $max_quantity);
    }

    /**
     * @return self<float>
     */
    public static function decimal(float $quantity, float $available_quantity = null, float $max_quantity = null, string $quantity_unit = null): self
    {
        return new self($quantity, QuantityType::Decimal(), $quantity_unit, $available_quantity, $max_quantity);
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

    /**
     * @return T|null
     */
    public function getAvailableQuantity()
    {
        return $this->available_quantity;
    }

    /**
     * @return T|null
     */
    public function getMaxQuantity()
    {
        return $this->max_quantity;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
