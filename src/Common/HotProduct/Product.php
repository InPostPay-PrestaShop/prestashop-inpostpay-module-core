<?php

declare(strict_types=1);

namespace izi\prestashop\Common\HotProduct;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\Price;
use izi\prestashop\Common\Product\ProductAttribute;
use izi\prestashop\Common\Product\ProductImage;

final class Product implements \JsonSerializable
{
    use ProductTrait;

    /**
     * @param ProductImage[] $additional_product_images
     * @param ProductAttribute[] $product_attributes
     */
    public function __construct(string $product_name, string $product_description, string $product_image, Price $price, Currency $currency, Quantity $quantity, ?string $ean = null, ?ProductAvailability $product_availability = null, array $additional_product_images = [], array $product_attributes = [])
    {
        $this->product_name = $product_name;
        $this->product_description = $product_description;
        $this->product_image = $product_image;
        $this->price = $price;
        $this->currency = $currency;
        $this->quantity = $quantity;
        $this->ean = $ean;
        $this->product_availability = $product_availability;
        $this->additional_product_images = $additional_product_images;
        $this->product_attributes = $product_attributes;
    }

    public function asIdentifiable(string $id): IdentifiableProduct
    {
        return new IdentifiableProduct(
            $id,
            $this->product_name,
            $this->product_description,
            $this->product_image,
            $this->price,
            $this->currency,
            $this->quantity,
            $this->ean,
            $this->product_availability,
            $this->additional_product_images,
            $this->product_attributes
        );
    }
}
