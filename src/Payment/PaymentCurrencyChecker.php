<?php

declare(strict_types=1);

namespace izi\prestashop\Payment;

final class PaymentCurrencyChecker
{
    public function check(\PaymentModule $paymentModule, int $currencyId): bool
    {
        if (0 >= $currencyId) {
            return false;
        }

        if (!$paymentModule->currencies) {
            return true;
        }

        $currencies = $paymentModule->getCurrency($currencyId);

        if (false === $currencies) {
            return false;
        }

        if ($currencies instanceof \Currency) {
            return $currencyId === (int) $currencies->id;
        }

        return \in_array($currencyId, array_map('intval', array_column($currencies, 'id_currency')), true);
    }
}
