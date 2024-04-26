<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Payment;

use izi\prestashop\BasketApp\Payment\Response\AvailablePaymentOptions;

interface PaymentsApiClientInterface
{
    public function getAvailablePaymentOptions(): AvailablePaymentOptions;
}
