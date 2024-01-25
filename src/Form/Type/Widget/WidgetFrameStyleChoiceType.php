<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\View\Widget\FrameStyle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WidgetFrameStyleChoiceType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'widgetframestylechoicetype';

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
            'data_class' => FrameStyle::class,
            'choices' => [
                $this->module->l('Rounded', self::TRANSLATION_SOURCE) => FrameStyle::Rounded(),
                $this->module->l('Round', self::TRANSLATION_SOURCE) => FrameStyle::Round(),
            ],
            'required' => false,
            'placeholder' => $this->module->l('Rectangular', self::TRANSLATION_SOURCE),
        ]);
    }
}
