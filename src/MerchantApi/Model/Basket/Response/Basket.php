<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Response;

use izi\prestashop\Common\Basket\AvailablePromotion;
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
     * @param Product[] $products
     * @param Consent[] $consents
     * @param PromoCode[] $promo_codes
     * @param Product[] $related_products
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

    public function asIdentifiable(string $id): IdentifiableBasket
    {
        return new IdentifiableBasket(
            $id,
            $this->summary,
            $this->delivery,
            $this->products,
            $this->consents,
            $this->promo_codes,
            $this->related_products,
            $this->promotions_available
        );
    }
}
