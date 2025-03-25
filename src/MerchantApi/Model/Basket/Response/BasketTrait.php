<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Response;

use izi\prestashop\Common\Basket\Consent;
use izi\prestashop\Common\Basket\DeliveryOption;
use izi\prestashop\Common\Basket\Product;
use izi\prestashop\Common\Basket\Summary;
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

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
