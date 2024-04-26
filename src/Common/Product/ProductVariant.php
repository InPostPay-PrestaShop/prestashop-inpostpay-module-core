<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Product;

final class ProductVariant implements \JsonSerializable
{
    /**
     * @var string
     */
    private $variant_id;

    /**
     * @var string
     */
    private $variant_name;

    /**
     * @var string|null
     */
    private $variant_description;

    /**
     * @var string|null
     */
    private $variant_type;

    /**
     * @var string|null
     */
    private $variant_values;

    public function __construct(string $variant_id, string $variant_name, ?string $variant_description = null, ?string $variant_type = null, ?string $variant_values = null)
    {
        $this->variant_id = $variant_id;
        $this->variant_name = $variant_name;
        $this->variant_description = $variant_description;
        $this->variant_type = $variant_type;
        $this->variant_values = $variant_values;
    }

    public function getId(): string
    {
        return $this->variant_id;
    }

    public function getName(): string
    {
        return $this->variant_name;
    }

    public function getDescription(): ?string
    {
        return $this->variant_description;
    }

    public function getType(): ?string
    {
        return $this->variant_type;
    }

    public function getValues(): ?string
    {
        return $this->variant_values;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
