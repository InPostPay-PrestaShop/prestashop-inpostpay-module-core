<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Provider;

interface DefaultCurrencyProviderInterface
{
    public function getDefaultCurrency(): \Currency;
}
