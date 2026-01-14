<?php

declare(strict_types=1);

use x13pricehistory\Providers\BatchLowestPriceProvider;

abstract class X13PriceHistory extends Module
{
    /**
     * @var BatchLowestPriceProvider
     */
    public $batchLowestPriceProvider;
}
