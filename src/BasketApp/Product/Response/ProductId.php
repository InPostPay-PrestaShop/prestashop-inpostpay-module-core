<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Product\Response;

final class ProductId implements \JsonSerializable
{
    /**
     * @var string
     */
    private $product_id;

    /**
     * @var string|null
     */
    private $qr_code;

    /**
     * @var string|null
     */
    private $deep_link;

    public function __construct(string $product_id, ?string $qr_code = null, ?string $deep_link = null)
    {
        $this->product_id = $product_id;
        $this->qr_code = $qr_code;
        $this->deep_link = $deep_link;
    }

    public function getId(): string
    {
        return $this->product_id;
    }

    public function getQrCode(): ?string
    {
        return $this->qr_code;
    }

    public function getDeepLink(): ?string
    {
        return $this->deep_link;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
