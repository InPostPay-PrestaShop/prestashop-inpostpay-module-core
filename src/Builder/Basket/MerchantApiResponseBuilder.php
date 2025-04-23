<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Common\Basket\AvailablePromotion;
use izi\prestashop\Common\Basket\Consent;
use izi\prestashop\Common\Basket\DeliveryOption;
use izi\prestashop\Common\Basket\Product;
use izi\prestashop\Common\Basket\Summary;
use izi\prestashop\Common\PromoCode;
use izi\prestashop\MerchantApi\Model\Basket\Response\Basket;

final class MerchantApiResponseBuilder extends AbstractBasketBuilder implements MerchantApiResponseBuilderInterface
{
    public function build(): Basket
    {
        return parent::build();
    }

    /**
     * @param DeliveryOption[] $delivery
     * @param Product[] $products
     * @param Consent[] $consents
     * @param PromoCode[] $promoCodes
     * @param Product[] $relatedProducts
     * @param AvailablePromotion[] $availablePromotions
     */
    protected function doBuild(Summary $summary, array $delivery, array $products, array $consents, array $promoCodes, array $relatedProducts, array $availablePromotions = []): Basket
    {
        return new Basket(
            $summary,
            $delivery,
            $products,
            $consents,
            $promoCodes,
            $relatedProducts,
            $availablePromotions
        );
    }
}
