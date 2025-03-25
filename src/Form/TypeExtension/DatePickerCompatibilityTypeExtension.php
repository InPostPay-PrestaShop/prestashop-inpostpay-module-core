<?php

declare(strict_types=1);

namespace izi\prestashop\Form\TypeExtension;

use PrestaShopBundle\Form\Admin\Type\DatePickerType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Polyfill for PS < 1.7.7.
 *
 * @internal
 */
final class DatePickerCompatibilityTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [DatePickerType::class];
    }

    public function getExtendedType(): string
    {
        return DatePickerType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($view->vars['date_format'])) {
            return;
        }

        $view->vars['attr']['data-format'] = $options['date_format'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        if ($resolver->isDefined('date_format')) {
            return;
        }

        $resolver
            ->setDefaults([
                'date_format' => 'YYYY-MM-DD',
            ])
            ->setAllowedTypes('date_format', 'string');
    }
}
