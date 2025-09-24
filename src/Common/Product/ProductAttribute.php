<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Product;

final class ProductAttribute implements \JsonSerializable
{
    /**
     * @var string
     */
    private $attribute_name;

    /**
     * @var string
     */
    private $attribute_value;

    public function __construct(string $attribute_name, string $attribute_value)
    {
        $this->attribute_name = $attribute_name;
        $this->attribute_value = $attribute_value;
    }

    public function getName(): string
    {
        return $this->attribute_name;
    }

    public function getValue(): string
    {
        return $this->attribute_value;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
