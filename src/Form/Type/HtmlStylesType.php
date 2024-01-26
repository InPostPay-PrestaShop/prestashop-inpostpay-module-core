<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\HtmlStyles;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class HtmlStylesType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'htmlstylestype';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('marginTop', IntegerType::class, [
                'required' => false,
                'label' => $this->module->l('Margin top', self::TRANSLATION_SOURCE),
                'unit' => 'px',
            ])
            ->add('marginLeft', IntegerType::class, [
                'required' => false,
                'label' => $this->module->l('Margin left', self::TRANSLATION_SOURCE),
                'unit' => 'px',
            ])
            ->add('marginRight', IntegerType::class, [
                'required' => false,
                'label' => $this->module->l('Margin right', self::TRANSLATION_SOURCE),
                'unit' => 'px',
            ])
            ->add('marginBottom', IntegerType::class, [
                'required' => false,
                'label' => $this->module->l('Margin bottom', self::TRANSLATION_SOURCE),
                'unit' => 'px',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HtmlStyles::class,
        ]);
    }
}
