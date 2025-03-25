<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\Request;

use izi\prestashop\Common\Basket\AvailablePromotion;
use izi\prestashop\Common\Basket\Consent;
use izi\prestashop\Common\Basket\DeliveryOption;
use izi\prestashop\Common\Basket\Product;
use izi\prestashop\Common\Basket\Summary;
use izi\prestashop\Common\PromoCode;

final class Basket implements \JsonSerializable
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

    /**
     * @param DeliveryOption[] $delivery
     * @param PromoCode[] $promo_codes
     * @param Product[] $products
     * @param Product[] $related_products
     * @param Consent[] $consents
     * @param AvailablePromotion[] $promotions_available
     */
    public function __construct(Summary $summary, array $delivery, array $products, array $consents, array $promo_codes = [], array $related_products = [], array $promotions_available = [])
    {
        $this->summary = $summary;
        $this->delivery = $delivery;
        $this->promo_codes = $promo_codes;
        $this->products = $products;
        $this->related_products = $related_products;
        $this->consents = $consents;
        $this->promotions_available = $promotions_available;
    }

    public function getSummary(): Summary
    {
        return $this->summary;
    }

    /**
     * @return DeliveryOption[]
     */
    public function getDelivery(): array
    {
        return $this->delivery;
    }

    /**
     * @return PromoCode[]
     */
    public function getPromoCodes(): array
    {
        return $this->promo_codes;
    }

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @return Product[]
     */
    public function getRelatedProducts(): array
    {
        return $this->related_products;
    }

    /**
     * @return Consent[]
     */
    public function getConsents(): array
    {
        return $this->consents;
    }

    /**
     * @return AvailablePromotion[]
     */
    public function getPromotionsAvailable(): array
    {
        return $this->promotions_available;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
