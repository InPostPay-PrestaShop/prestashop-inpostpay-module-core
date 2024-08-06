<?php

declare(strict_types=1);

namespace izi\prestashop\Form\TypeExtension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * "Flips" the default "choices_as_values" option value on Sf 2.8.
 *
 * @internal
 */
final class ChoicesAsValuesTypeExtension extends AbstractTypeExtension
{
    public function getExtendedType(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        if (!$resolver->isDefined('choices_as_values')) {
            return;
        }

        $resolver->setDefault('choices_as_values', true);
    }
}
