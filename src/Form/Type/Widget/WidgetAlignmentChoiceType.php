<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\View\Widget\Alignment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WidgetAlignmentChoiceType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'widgetalignmentchoicetype';

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
            'data_class' => Alignment::class,
            'choices' => [
                $this->module->l('Left', self::TRANSLATION_SOURCE) => Alignment::Left(),
                $this->module->l('Center', self::TRANSLATION_SOURCE) => Alignment::Center(),
                $this->module->l('Right', self::TRANSLATION_SOURCE) => Alignment::Right(),
            ],
        ]);
    }
}
