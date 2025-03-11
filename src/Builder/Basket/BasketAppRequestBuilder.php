<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\BasketApp\Basket\Request\Basket;
use izi\prestashop\Common\Basket\Summary;

final class BasketAppRequestBuilder extends AbstractBasketBuilder implements BasketAppRequestBuilderInterface
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
