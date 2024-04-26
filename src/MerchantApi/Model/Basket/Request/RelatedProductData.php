<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Request;

final class RelatedProductData implements \JsonSerializable
{
    /**
     * @var string
     */
    private $product_id;

    /**
     * @var Quantity
     */
    private $quantity;

    /**
     * @var string|null
     */
    private $ean;

    public function __construct(string $product_id, Quantity $quantity, ?string $ean = null)
    {
        $this->product_id = $product_id;
        $this->quantity = $quantity;
        $this->ean = $ean;
    }

    public function getProductId(): string
    {
        return $this->product_id;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
