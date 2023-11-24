<?php

namespace izi\prestashop\traits;

trait PriceFactoryTrait
{
    /**
     * @param float $net
     * @param float $gross
     *
     * @return \izi\item\Price
     */
    private function createPrice($net, $gross)
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

    /**
     * @param float $price
     *
     * @return string
     */
    private function formatPrice($price)
    {
        return number_format($price, 2, '.', '');
    }
}
