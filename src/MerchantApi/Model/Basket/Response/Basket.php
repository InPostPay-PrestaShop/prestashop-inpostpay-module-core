<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Response;

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
     * @param DeliveryOption[] $delivery
     * @param PromoCode[] $promo_codes
     * @param Product[] $products
     * @param Product[] $related_products
     * @param Consent[] $consents
     */
    public function __construct(Summary $summary, array $delivery, array $products, array $consents, array $promo_codes = [], array $related_products = [])
    {
        $this->summary = $summary;
        $this->delivery = $delivery;
        $this->promo_codes = $promo_codes;
        $this->products = $products;
        $this->related_products = $related_products;
        $this->consents = $consents;
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

    public function getPromoCodes(): array
    {
        return $this->promo_codes;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getRelatedProducts(): array
    {
        return $this->related_products;
    }

    public function getConsents(): array
    {
        return $this->consents;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
