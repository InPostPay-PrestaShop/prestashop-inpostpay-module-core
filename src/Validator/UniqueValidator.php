<?php

declare(strict_types=1);

namespace izi\prestashop\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @author Yevgeniy Zholkevskiy <zhenya.zholkevskiy@gmail.com>
 */
final class UniqueValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Unique) {
            throw new UnexpectedTypeException($constraint, Unique::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_array($value) && !$value instanceof \IteratorAggregate) {
            throw new UnexpectedValueException($value, 'array|IteratorAggregate');
        }

        $collectionElements = [];
        $normalizer = $this->getNormalizer($constraint);

        foreach ($value as $element) {
            $element = $normalizer($element);

            if (!in_array($element, $collectionElements, true)) {
                $collectionElements[] = $element;

                continue;
            }

            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($element))
                ->addViolation();

            return;
        }
    }

    private function getNormalizer(Unique $unique): callable
    {
        return $unique->normalizer ?? static function ($value) {
            return $value;
        };
    }
}
