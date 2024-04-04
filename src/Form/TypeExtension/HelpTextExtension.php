<?php

declare(strict_types=1);

namespace izi\prestashop\Form\TypeExtension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Polyfill for PS < 1.7.6.
 *
 * @internal
 */
final class HelpTextExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function getExtendedType(): string
    {
        return FormType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['help'] = $options['help'] ?? null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        if ($resolver->isDefined('help')) {
            return;
        }

        $resolver
            ->setDefaults([
                'help' => null,
            ])
            ->setAllowedTypes('help', ['null', 'string']);
    }
}
