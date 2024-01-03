<?php

declare(strict_types=1);

namespace izi\prestashop\Builder;

use izi\prestashop\Common\Price;

class PriceFactory
{
    public static function create(float $net, float $gross): Price
    {
        $net = \Tools::ps_round($net, 2);
        $gross = \Tools::ps_round($gross, 2);
        $vat = $gross - $net;

        return new Price($net, $gross, $vat);
    }
}
