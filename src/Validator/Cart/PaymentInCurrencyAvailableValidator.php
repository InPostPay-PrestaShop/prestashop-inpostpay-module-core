<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Cart;

use izi\prestashop\Payment\PaymentCurrencyChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class PaymentInCurrencyAvailableValidator extends ConstraintValidator
{
    /**
     * @var PaymentCurrencyChecker
     */
    private $currencyChecker;

    public function __construct(PaymentCurrencyChecker $currencyChecker)
    {
        $this->currencyChecker = $currencyChecker;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof PaymentInCurrencyAvailable) {
            throw new UnexpectedTypeException($constraint, PaymentInCurrencyAvailable::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof \Cart) {
            throw new UnexpectedTypeException($value, \Cart::class);
        }

        if ($this->currencyChecker->check($constraint->paymentModule, (int) $value->id_currency)) {
            return;
        }

        $this->context
            ->buildViolation('Payment option is not available for the selected currency.')
            ->addViolation();
    }
}
