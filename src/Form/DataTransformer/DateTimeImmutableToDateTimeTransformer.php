<?php

declare(strict_types=1);

namespace izi\prestashop\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Polyfill for Sf < 4.1
 *
 * @see \Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeImmutableToDateTimeTransformer
 *
 * @internal
 */
final class DateTimeImmutableToDateTimeTransformer implements DataTransformerInterface
{
    public function transform($value): ?\DateTime
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \DateTimeImmutable) {
            throw new TransformationFailedException('Expected a \DateTimeImmutable.');
        }

        if (\PHP_VERSION_ID >= 70300) {
            return \DateTime::createFromImmutable($value);
        }

        return \DateTime::createFromFormat('U.u', $value->format('U.u'))->setTimezone($value->getTimezone());
    }

    public function reverseTransform($value): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \DateTime) {
            throw new TransformationFailedException('Expected a \DateTime.');
        }

        return \DateTimeImmutable::createFromMutable($value);
    }
}
