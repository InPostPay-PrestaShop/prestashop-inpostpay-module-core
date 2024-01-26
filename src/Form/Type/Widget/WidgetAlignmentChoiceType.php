<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Form\DataTransformer\EnumDataTransformer;
use izi\prestashop\View\Widget\Alignment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
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

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new EnumDataTransformer(Alignment::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                $this->module->l('Left', self::TRANSLATION_SOURCE) => Alignment::Left()->value,
                $this->module->l('Center', self::TRANSLATION_SOURCE) => Alignment::Center()->value,
                $this->module->l('Right', self::TRANSLATION_SOURCE) => Alignment::Right()->value,
            ],
        ]);
    }
}
