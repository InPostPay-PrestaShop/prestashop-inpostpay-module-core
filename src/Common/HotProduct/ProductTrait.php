<?php

declare(strict_types=1);

namespace izi\prestashop\Common\HotProduct;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\Price;
use izi\prestashop\Common\Product\ProductAttribute;
use izi\prestashop\Common\Product\ProductImage;

/**
 * @internal
 */
trait ProductTrait
{
    /**
     * @var string
     */
    private $ean;

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
     * @var string
     */
    private $product_link;

    public function getEan(): string
    {
        return $this->ean;
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

    /**
     * @return Quantity
     */
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

    public function getLink(): string
    {
        return $this->product_link;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    private function setLink(string $link): void
    {
        if ('' === $link) {
            @trigger_error('Passing a product link will be required in the future version of InPost Pay.', E_USER_DEPRECATED);
        }

        $this->product_link = $link;
    }

    private function setEan(?string $ean): void
    {
        if ('' === $ean = (string) $ean) {
            @trigger_error('Passing an EAN code will be required in the future version of InPost Pay.', E_USER_DEPRECATED);
        }

        $this->ean = $ean;
    }
}
