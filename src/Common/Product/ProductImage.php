<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Product;

final class ProductImage implements \JsonSerializable
{
    /**
     * @var string
     */
    private $small_size;

    /**
     * @var string
     */
    private $normal_size;

    public function __construct(string $small_size, string $normal_size)
    {
        $this->small_size = $small_size;
        $this->normal_size = $normal_size;
    }

    public function getSmallSize(): string
    {
        return $this->small_size;
    }

    public function getNormalSize(): string
    {
        return $this->normal_size;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
