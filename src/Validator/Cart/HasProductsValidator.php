<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Cart;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class HasProductsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof HasProducts) {
            throw new UnexpectedTypeException($constraint, HasProducts::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof \Cart) {
            throw new UnexpectedTypeException($value, \Cart::class);
        }

        if ([] !== $value->getProducts()) {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->addViolation();
    }
}
