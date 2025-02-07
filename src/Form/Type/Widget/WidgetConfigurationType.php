<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\View\Widget\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WidgetConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'widgetconfigurationtype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['for_v2'] = $options['for_v2'];

        if (!$options['for_v2']) {
            return;
        }

        $data = $form->getViewData();

        $view->vars['preview_container_styles'] = $data instanceof Configuration ? $data->getV2ContainerStyles() : [];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alignment', WidgetAlignmentChoiceType::class, [
                'label' => $this->translator->l('Alignment', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Specifies the orientation of the widget in the space available for it. If your template allocates a narrow space for the widget the setting will not affect the appearance.', self::TRANSLATION_SOURCE),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                ],
            ])
            ->add('darkMode', ChoiceType::class, [
                'label' => $this->translator->l('Background', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Determines whether the widget is on a light or dark background in your store. The setting affects the font color, make sure it is visible.', self::TRANSLATION_SOURCE),
                'choices' => [
                    $this->translator->l('Light', self::TRANSLATION_SOURCE) => false,
                    $this->translator->l('Dark', self::TRANSLATION_SOURCE) => true,
                ],
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                ],
            ])
            ->add('variant', WidgetVariantChoiceType::class, [
                'label' => $this->translator->l('Variant', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('The widget is available in 2 color variants. Choose the one more suitable for your store\'s color scheme.', self::TRANSLATION_SOURCE),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                ],
            ])
            ->add('frameStyle', WidgetFrameStyleChoiceType::class, [
                'label' => $this->translator->l('Frame style', self::TRANSLATION_SOURCE),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                ],
            ]);

        if ($options['for_v2']) {
            $builder
                ->add('size', WidgetSizeChoiceType::class, [
                    'label' => $this->translator->l('Size', self::TRANSLATION_SOURCE),
                    'attr' => [
                        'class' => 'js-widget-attribute-provider',
                    ],
                ])
                ->add('maxWidthPx', IntegerType::class, [
                    'required' => false,
                    'label' => $this->translator->l('Max width', self::TRANSLATION_SOURCE),
                    'attr' => [
                        'class' => 'js-widget-attribute-provider',
                    ],
                    'unit' => 'px',
                ]);
        } else {
            $builder
                ->add('minWidthPx', IntegerType::class, [
                    'required' => false,
                    'label' => $this->translator->l('Min width', self::TRANSLATION_SOURCE),
                    'attr' => [
                        'class' => 'js-widget-container-style-provider',
                    ],
                    'unit' => 'px',
                ])
                ->add('maxWidthPx', IntegerType::class, [
                    'required' => false,
                    'label' => $this->translator->l('Max width', self::TRANSLATION_SOURCE),
                    'attr' => [
                        'class' => 'js-widget-attribute-provider',
                    ],
                    'unit' => 'px',
                ])
                ->add('minHeightPx', IntegerType::class, [
                    'required' => false,
                    'label' => $this->translator->l('Min height', self::TRANSLATION_SOURCE),
                    'attr' => [
                        'class' => 'js-widget-attribute-provider',
                    ],
                    'unit' => 'px',
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => Configuration::class,
                'for_v2' => true,
            ])
            ->setAllowedTypes('for_v2', 'bool');

        if (is_callable([$resolver, 'setDeprecated'])) {
            $resolver->setDeprecated('for_v2');
        }
    }
}
