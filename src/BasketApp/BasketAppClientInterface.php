<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp;

use izi\prestashop\BasketApp\Basket\BasketsApiClientInterface;
use izi\prestashop\BasketApp\Order\OrdersApiClientInterface;
use izi\prestashop\BasketApp\Payment\PaymentsApiClientInterface;
use izi\prestashop\BasketApp\Product\ProductsApiClientInterface;
use izi\prestashop\BasketApp\Signature\SigningKeysApiClientInterface;

interface BasketAppClientInterface extends BasketsApiClientInterface, OrdersApiClientInterface, SigningKeysApiClientInterface, PaymentsApiClientInterface, ProductsApiClientInterface
{
    public const DATETIME_FORMAT = 'Y-m-d\TH:i:s.u\Z'; // format character "p" is not available before PHP 8.0
    public const DATETIME_ZONE = 'UTC';
}
