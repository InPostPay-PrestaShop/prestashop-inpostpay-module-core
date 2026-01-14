<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Cart;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

final class PaymentInCurrencyAvailable extends Constraint
{
    /**
     * @var \PaymentModule
     */
    public $paymentModule;

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (!$this->paymentModule instanceof \PaymentModule) {
            throw new InvalidArgumentException(\sprintf('The "paymentModule" option must be an instance of "%s", "%s" given.', \PaymentModule::class, get_debug_type($this->paymentModule)));
        }
    }

    public function getDefaultOption(): string
    {
        return 'paymentModule';
    }

    public function getRequiredOptions(): array
    {
        return ['paymentModule'];
    }
}
