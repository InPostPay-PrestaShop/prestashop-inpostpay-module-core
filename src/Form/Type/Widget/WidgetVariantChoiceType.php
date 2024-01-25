<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\View\Widget\Variant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WidgetVariantChoiceType extends AbstractType
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
            'data_class' => Variant::class,
            'choices' => [
                $this->module->l('Yellow', self::TRANSLATION_SOURCE) => Variant::Primary(),
                $this->module->l('Black', self::TRANSLATION_SOURCE) => Variant::Secondary(),
            ],
        ]);
    }
}
