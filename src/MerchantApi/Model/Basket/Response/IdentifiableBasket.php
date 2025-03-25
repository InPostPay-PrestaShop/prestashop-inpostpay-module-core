<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Response;

use izi\prestashop\Common\Basket\Consent;
use izi\prestashop\Common\Basket\DeliveryOption;
use izi\prestashop\Common\Basket\Product;
use izi\prestashop\Common\Basket\Summary;
use izi\prestashop\Common\PromoCode;

final class IdentifiableBasket extends Basket
{
    /**
     * @var string
     */
    private $basket_id;

    /**
     * @param DeliveryOption[] $delivery
     * @param PromoCode[] $promo_codes
     * @param Product[] $products
     * @param Product[] $related_products
     * @param Consent[] $consents
     */
    public function __construct(string $basket_id, Summary $summary, array $delivery, array $products, array $consents, array $promo_codes = [], array $related_products = [])
    {
        parent::__construct($summary, $delivery, $products, $consents, $promo_codes, $related_products);
        $this->basket_id = $basket_id;
    }

    public function getId(): string
    {
        return $this->basket_id;
    }

    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + [
            'basket_id' => $this->basket_id,
        ];
    }
}
