<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Product;

use izi\prestashop\Product\ProductType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotOfTypeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotOfType) {
            throw new UnexpectedTypeException($constraint, NotOfType::class);
        }

        if (!\is_array($value) && !$value instanceof \ArrayAccess) {
            throw new UnexpectedTypeException($value, 'array|ArrayAccess');
        }

        $baseType = ProductType::fromProductData($value);

        foreach ($constraint->types as $type) {
            if (
                $baseType !== $type
                && (ProductType::Customizable() !== $type || !$this->isCustomizable($value))
            ) {
                continue;
            }

            $this->context
                ->buildViolation('Product is of restricted type "{{ type }}".')
                ->setParameter('{{ type }}', $type->value)
                ->addViolation();
        }
    }

    private function isCustomizable($product): bool
    {
        return 0 < ($product['customizable'] ?? 0);
    }
}
