<?php

declare(strict_types=1);

namespace izi\prestashop\Form\TypeExtension;

use izi\prestashop\Form\DataTransformer\DateTimeImmutableToDateTimeTransformer as TransformerPolyfill;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeImmutableToDateTimeTransformer;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds input option not present before Sf 4.1.
 *
 * @internal
 */
final class DateTimeImmutableTimeTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [TimeType::class];
    }

    public function getExtendedType(): string
    {
        return TimeType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ('datetime_immutable' !== $options['input'] || class_exists(DateTimeImmutableToDateTimeTransformer::class)) {
            return;
        }

        $builder->addModelTransformer(new TransformerPolyfill(), true);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        if (class_exists(DateTimeImmutableToDateTimeTransformer::class)) {
            return;
        }

        $resolver->addAllowedValues('input', [
            'datetime_immutable',
        ]);
    }
}
