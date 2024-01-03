<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Builder\Basket\BasketAppRequestBuilderInterface as RequestBuilder;
use izi\prestashop\Builder\Basket\MerchantApiResponseBuilderInterface as ResponseBuilder;
use izi\prestashop\Entities\BasketInterface;

interface BasketBuilderFactoryInterface
{
    public function createRequestBuilder(BasketInterface $basket): RequestBuilder;

    public function createResponseBuilder(BasketInterface $basket): ResponseBuilder;
}
