<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WidgetDarkModeChoiceType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'widgetvariantchoicetype';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                $this->module->l('Light', self::TRANSLATION_SOURCE) => false,
                $this->module->l('Dark', self::TRANSLATION_SOURCE) => true,
            ],
        ]);
    }
}
