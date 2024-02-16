<?php

declare(strict_types=1);

namespace izi\prestashop\Form\TypeExtension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines options not present before PS 8.
 *
 * @internal
 */
final class UnitTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [IntegerType::class];
    }

    public function getExtendedType(): string
    {
        return IntegerType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($options['unit'])) {
            $view->vars['unit'] = $options['unit'];
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        if (!$resolver->isDefined('unit')) {
            $resolver
                ->setDefined('unit')
                ->setAllowedTypes('unit', 'string');
        }
    }
}
