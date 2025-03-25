<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Product\Response;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\HotProduct\ProductAvailability;
use izi\prestashop\Common\HotProduct\Quantity;
use izi\prestashop\Common\Price;
use izi\prestashop\Common\Product\ProductAttribute;
use izi\prestashop\Common\Product\ProductImage;

final class Product implements \JsonSerializable
{
    /**
     * @var string
     */
    private $product_id;

    /**
     * @var Status
     */
    private $status;

    /**
     * @var string|null
     */
    private $ean;

    /**
     * @var string|null
     */
    private $qr_code;

    /**
     * @var string|null
     */
    private $deep_link;

    /**
     * @var ProductAvailability|null
     */
    private $product_availability;

    /**
     * @var string
     */
    private $product_name;

    /**
     * @var string
     */
    private $product_description;

    /**
     * @var string
     */
    private $product_image;

    /**
     * @var ProductImage[]
     */
    private $additional_product_images;

    /**
     * @var Price
     */
    private $price;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var Quantity
     */
    private $quantity;

    /**
     * @var ProductAttribute[]
     */
    private $product_attributes;

    /**
     * @param ProductImage[] $additional_product_images
     * @param ProductAttribute[] $product_attributes
     */
    public function __construct(string $product_id, Status $status, string $product_name, string $product_description, string $product_image, Price $price, Currency $currency, Quantity $quantity, ?string $ean = null, ?string $qr_code = null, ?string $deep_link = null, ?ProductAvailability $product_availability = null, array $additional_product_images = [], array $product_attributes = [])
    {
        $this->product_id = $product_id;
        $this->status = $status;
        $this->product_name = $product_name;
        $this->product_description = $product_description;
        $this->product_image = $product_image;
        $this->price = $price;
        $this->currency = $currency;
        $this->quantity = $quantity;
        $this->ean = $ean;
        $this->qr_code = $qr_code;
        $this->deep_link = $deep_link;
        $this->product_availability = $product_availability;
        $this->additional_product_images = $additional_product_images;
        $this->product_attributes = $product_attributes;
    }

    public function getId(): string
    {
        return $this->product_id;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function getQrCode(): ?string
    {
        return $this->qr_code;
    }

    public function getDeepLink(): ?string
    {
        return $this->deep_link;
    }

    public function getAvailability(): ?ProductAvailability
    {
        return $this->product_availability;
    }

    public function getName(): string
    {
        return $this->product_name;
    }

    public function getDescription(): string
    {
        return $this->product_description;
    }

    public function getImageUrl(): string
    {
        return $this->product_image;
    }

    /**
     * @return ProductImage[]
     */
    public function getAdditionalImages(): array
    {
        return $this->additional_product_images;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    /**
     * @return ProductAttribute[]
     */
    public function getAttributes(): array
    {
        return $this->product_attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
