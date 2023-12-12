<?php

namespace izi\prestashop\traits;

trait PriceFactoryTrait
{
    private function createPrice(float $net, float $gross): \izi\item\Price
    {
        $net = \Tools::ps_round($net, 2);
        $gross = \Tools::ps_round($gross, 2);
        $vat = $gross - $net;

        $price = new \izi\item\Price();

        $price->net = $this->formatPrice($net);
        $price->gross = $this->formatPrice($gross);
        $price->vat = $this->formatPrice($vat);

        return $price;
    }

    private function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }
}
