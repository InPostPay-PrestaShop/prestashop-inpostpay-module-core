<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

use izi\prestashop\Common\PromoCode;

/**
 * @internal
 */
trait BasketTrait
{
    /**
     * @var Summary
     */
    private $summary;

    /**
     * @var DeliveryOption[]
     */
    private $delivery;

    /**
     * @var PromoCode[]
     */
    private $promo_codes;

    /**
     * @var Product[]
     */
    private $products;

    /**
     * @var Product[]
     */
    private $related_products;

    /**
     * @var Consent[]
     */
    private $consents;

    /**
     * @var AvailablePromotion[]
     */
    private $promotions_available;

    public function getSummary(): Summary
    {
        return $this->summary;
    }

    /**
     * @return static
     */
    public function withSummary(Summary $summary): self
    {
        $basket = clone $this;
        $basket->summary = $summary;

        return $basket;
    }

    /**
     * @return DeliveryOption[]
     */
    public function getDelivery(): array
    {
        return $this->delivery;
    }

    /**
     * @param DeliveryOption[] $delivery
     *
     * @return static
     */
    public function withDelivery(array $delivery): self
    {
        $basket = clone $this;
        $basket->delivery = $delivery;

        return $basket;
    }

    /**
     * @return PromoCode[]
     */
    public function getPromoCodes(): array
    {
        return $this->promo_codes;
    }

    /**
     * @param PromoCode[] $promoCodes
     *
     * @return static
     */
    public function withPromoCodes(array $promoCodes): self
    {
        $basket = clone $this;
        $basket->promo_codes = $promoCodes;

        return $basket;
    }

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param Product[] $products
     *
     * @return static
     */
    public function withProducts(array $products): self
    {
        $basket = clone $this;
        $basket->products = $products;

        return $basket;
    }

    /**
     * @return Product[]
     */
    public function getRelatedProducts(): array
    {
        return $this->related_products;
    }

    /**
     * @param Product[] $products
     *
     * @return static
     */
    public function withRelatedProducts(array $products): self
    {
        $basket = clone $this;
        $basket->related_products = $products;

        return $basket;
    }

    /**
     * @return Consent[]
     */
    public function getConsents(): array
    {
        return $this->consents;
    }

    /**
     * @param Consent[] $consents
     *
     * @return static
     */
    public function withConsents(array $consents): self
    {
        $basket = clone $this;
        $basket->consents = $consents;

        return $basket;
    }

    /**
     * @return AvailablePromotion[]
     */
    public function getAvailablePromotions(): array
    {
        return $this->promotions_available;
    }

    /**
     * @param AvailablePromotion[] $promotions
     *
     * @return static
     */
    public function withAvailablePromotions(array $promotions): self
    {
        $basket = clone $this;
        $basket->promotions_available = $promotions;

        return $basket;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
