<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class MaskedPasswordType extends AbstractType
{
    public const MASKED_VALUE = '*****';

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ('' === $view->vars['value'] && $form->getData()) {
            $view->vars['value'] = self::MASKED_VALUE;
        }
    }

    public function getParent(): string
    {
        return PasswordType::class;
    }
}
