<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\Request;

use izi\prestashop\Common\Basket\AvailablePromotion;
use izi\prestashop\Common\Basket\BasketTrait;
use izi\prestashop\Common\Basket\Consent;
use izi\prestashop\Common\Basket\DeliveryOption;
use izi\prestashop\Common\Basket\Product;
use izi\prestashop\Common\Basket\Summary;
use izi\prestashop\Common\PromoCode;

final class Basket implements \JsonSerializable
{
    use BasketTrait;

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

    /**
     * @return AvailablePromotion[]
     *
     * @deprecated use {@see getAvailablePromotions()} instead
     */
    public function getPromotionsAvailable(): array
    {
        @trigger_error(\sprintf('The method "%s()" is deprecated since version 3.1, use "getAvailablePromotions()" instead.', __METHOD__), \E_USER_DEPRECATED);

        return $this->promotions_available;
    }
}
