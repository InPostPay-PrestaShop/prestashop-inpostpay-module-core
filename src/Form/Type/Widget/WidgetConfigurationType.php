<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\View\Widget\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                    'data-attribute' => 'class',
                    'data-value-pattern' => 'float-%s',
                ],
            ])
            ->add('darkMode', ChoiceType::class, [
                'label' => $this->module->l('Background', self::TRANSLATION_SOURCE),
                'choices' => [
                    $this->module->l('Light', self::TRANSLATION_SOURCE) => false,
                    $this->module->l('Dark', self::TRANSLATION_SOURCE) => true,
                ],
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                    'data-attribute' => 'dark_mode',
                ],
            ])
            ->add('variant', WidgetVariantChoiceType::class, [
                'label' => $this->module->l('Variant', self::TRANSLATION_SOURCE),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                    'data-attribute' => 'variant',
                ],
            ])
            ->add('frameStyle', WidgetFrameStyleChoiceType::class, [
                'label' => $this->module->l('Frame style', self::TRANSLATION_SOURCE),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                    'data-attribute' => 'frame_style',
                ],
            ])
            ->add('minWidthPx', IntegerType::class, [
                'required' => false,
                'label' => $this->module->l('Min width', self::TRANSLATION_SOURCE),
                'attr' => [
                    'class' => 'js-widget-container-style-provider',
                    'data-style' => 'min-width',
                ],
                'unit' => 'px',
            ])
            ->add('maxWidthPx', IntegerType::class, [
                'required' => false,
                'label' => $this->module->l('Max width', self::TRANSLATION_SOURCE),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                    'data-attribute' => 'max_width',
                ],
                'unit' => 'px',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
        ]);
    }
}
