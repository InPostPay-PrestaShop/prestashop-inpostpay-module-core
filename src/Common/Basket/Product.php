<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

use izi\prestashop\Common\Price;
use izi\prestashop\Common\Product\ProductAttribute;
use izi\prestashop\Common\Product\ProductVariant;

final class Product implements \JsonSerializable
{
    /**
     * @var string
     */
    private $product_id;

    /**
     * @var string|null
     */
    private $product_category;

    /**
     * @var string|null
     */
    private $ean;

    /**
     * @var string
     */
    private $product_name;

    /**
     * @var string|null
     */
    private $product_description;

    /**
     * @var string|null
     */
    private $product_link;

    /**
     * @var string|null
     */
    private $product_image;

    /**
     * @var Price
     */
    private $base_price;

    /**
     * @var Price|null
     */
    private $promo_price;

    /**
     * @var Price|null
     */
    private $lowest_price;

    /**
     * @var Quantity
     */
    private $quantity;

    /**
     * @var ProductAttribute[]
     */
    private $product_attributes;

    /**
     * @var ProductVariant[]
     */
    private $variants;

    /**
     * @param ProductAttribute[] $product_attributes
     * @param ProductVariant[] $variants
     */
    public function __construct(string $product_id, string $product_name, Price $base_price, Quantity $quantity, string $product_category = null, string $ean = null, string $product_description = null, string $product_link = null, string $product_image = null, Price $promo_price = null, Price $lowest_price = null, array $product_attributes = [], array $variants = [])
    {
        $this->product_id = $product_id;
        $this->product_category = $product_category;
        $this->ean = $ean;
        $this->product_name = $product_name;
        $this->product_description = $product_description;
        $this->product_link = $product_link;
        $this->product_image = $product_image;
        $this->base_price = $base_price;
        $this->promo_price = $promo_price;
        $this->lowest_price = $lowest_price;
        $this->quantity = $quantity;
        $this->product_attributes = $product_attributes;
        $this->variants = $variants;
    }

    public function getId(): string
    {
        return $this->product_id;
    }

    public function getCategory(): ?string
    {
        return $this->product_category;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function getName(): string
    {
        return $this->product_name;
    }

    public function getDescription(): ?string
    {
        return $this->product_description;
    }

    public function getLink(): ?string
    {
        return $this->product_link;
    }

    public function getImageUrl(): ?string
    {
        return $this->product_image;
    }

    public function getBasePrice(): Price
    {
        return $this->base_price;
    }

    public function getPromoPrice(): ?Price
    {
        return $this->promo_price;
    }

    public function getLowestPrice(): ?Price
    {
        return $this->lowest_price;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getAttributes(): array
    {
        return $this->product_attributes;
    }

    /**
     * @return ProductVariant[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
