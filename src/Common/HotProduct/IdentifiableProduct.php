<?php

declare(strict_types=1);

namespace izi\prestashop\Common\HotProduct;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\Price;
use izi\prestashop\Common\Product\ProductAttribute;
use izi\prestashop\Common\Product\ProductImage;

final class IdentifiableProduct implements \JsonSerializable
{
    use ProductTrait;

    /**
     * @var string
     */
    private $product_id;

    /**
     * @param ProductImage[] $additional_product_images
     * @param ProductAttribute[] $product_attributes
     */
    public function __construct(string $product_id, string $product_name, string $product_description, string $product_image, Price $price, Currency $currency, Quantity $quantity, ?string $ean = null, ?ProductAvailability $product_availability = null, array $additional_product_images = [], array $product_attributes = [], string $product_link = '')
    {
        $this->product_id = $product_id;
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
        $this->setLink($product_link);
    }

    public function getId(): string
    {
        return $this->product_id;
    }
}
