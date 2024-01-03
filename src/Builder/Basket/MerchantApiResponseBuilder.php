<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Common\Basket\Summary;
use izi\prestashop\MerchantApi\Model\Basket\Response\Basket;

final class MerchantApiResponseBuilder extends AbstractBasketBuilder implements MerchantApiResponseBuilderInterface
{
    public function build(): Basket
    {
        return parent::build();
    }

    protected function doBuild(Summary $summary, array $delivery, array $products, array $consents, array $promoCodes, array $relatedProducts): Basket
    {
        return new Basket(
            $summary,
            $delivery,
            $products,
            $consents,
            $promoCodes,
            $relatedProducts
        );
    }
}
