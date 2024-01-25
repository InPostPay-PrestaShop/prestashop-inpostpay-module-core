<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\View\Widget\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

final class WidgetConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'widgetconfigurationtype';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alignment', WidgetAlignmentChoiceType::class, [
                'label' => $this->module->l('Alignment', self::TRANSLATION_SOURCE),
            ])
            ->add('darkMode', WidgetDarkModeChoiceType::class, [
                'label' => $this->module->l('Background', self::TRANSLATION_SOURCE),
            ])
            ->add('variant', WidgetVariantChoiceType::class, [
                'label' => $this->module->l('Variant', self::TRANSLATION_SOURCE),
            ])
            ->add('frameStyle', WidgetFrameStyleChoiceType::class, [
                'label' => $this->module->l('Frame style', self::TRANSLATION_SOURCE),
            ])
            ->add('minWidth', IntegerType::class, [
                'required' => false,
                'label' => $this->module->l('Min width', self::TRANSLATION_SOURCE),
                'constraints' => new Range([
                    'min' => Configuration::WIDTH_MIN_PX,
                    'max' => Configuration::WIDTH_MAX_PX,
                ]),
            ])
            ->add('maxWidth', IntegerType::class, [
                'required' => false,
                'label' => $this->module->l('Max width', self::TRANSLATION_SOURCE),
                'constraints' => new Range([
                    'min' => Configuration::WIDTH_MIN_PX,
                    'max' => Configuration::WIDTH_MAX_PX,
                ]),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
        ]);
    }
}
