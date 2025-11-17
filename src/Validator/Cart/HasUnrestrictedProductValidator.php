<?php

namespace izi\prestashop\Validator\Cart;

use izi\prestashop\Validator\Product\Unrestricted;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class HasUnrestrictedProductValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof HasUnrestrictedProduct) {
            throw new UnexpectedTypeException($constraint, HasUnrestrictedProduct::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof \Cart) {
            throw new UnexpectedTypeException($value, \Cart::class);
        }

        $productConstraint = new Unrestricted(['strict' => true]);

        foreach ($value->getProducts() as $product) {
            $validator = $this->context->getValidator()->startContext();
            $violations = $validator->validate($product, $productConstraint)->getViolations();

            if (0 === $violations->count()) {
                return;
            }
        }

        $this->context
            ->buildViolation($constraint->message)
            ->addViolation();
    }
}
