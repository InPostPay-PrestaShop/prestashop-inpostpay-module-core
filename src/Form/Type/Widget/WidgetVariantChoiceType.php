<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Form\DataTransformer\EnumDataTransformer;
use izi\prestashop\View\Widget\Variant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
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

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new EnumDataTransformer(Variant::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                $this->module->l('Yellow', self::TRANSLATION_SOURCE) => Variant::Primary()->value,
                $this->module->l('Black', self::TRANSLATION_SOURCE) => Variant::Secondary()->value,
            ],
        ]);
    }
}
