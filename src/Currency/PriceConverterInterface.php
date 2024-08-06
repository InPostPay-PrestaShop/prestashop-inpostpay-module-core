<?php

declare(strict_types=1);

namespace izi\prestashop\Currency;

interface PriceConverterInterface
{
    /**
     * @param \Currency|null $from if null, amount will be converted from the current shop's default currency
     */
    public function convert(float $amount, \Currency $target, ?\Currency $from = null): float;

    /**
     * @param int|null $fromCurrencyId if null, amount will be converted from the current shop's default currency
     */
    public function convertByIds(float $amount, int $targetCurrencyId, ?int $fromCurrencyId = null): float;
}
