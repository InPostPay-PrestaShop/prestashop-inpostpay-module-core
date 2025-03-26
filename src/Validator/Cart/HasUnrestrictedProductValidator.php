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

        foreach ($value->getProducts() as $product) {
            $violations = $this->context->getValidator()->startContext()->validate($product, new Unrestricted())->getViolations();

            if (0 === count($violations)) {
                return;
            }
        }

        $this->context
            ->buildViolation($constraint->message)
            ->setTranslationDomain('Shop.Notifications.Error')
            ->addViolation();
    }
}
