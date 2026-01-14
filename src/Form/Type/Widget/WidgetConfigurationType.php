<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Form\Type\EnumType;
use izi\prestashop\View\Widget\FrameStyle;
use izi\prestashop\View\Widget\Size;
use izi\prestashop\View\Widget\Variant;
use izi\prestashop\View\Widget\WidgetConfiguration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WidgetConfigurationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['preview_container_styles'] = $options['preview_container_styles'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('darkMode', ChoiceType::class, [
                'label' => $this->translator->trans('Background', [], 'Modules.Inpostizi.Gui'),
                'help' => $this->translator->trans('Determines whether the widget is displayed on a light or dark background in your store. This setting affects the font color - make sure it is visible.', [], 'Modules.Inpostizi.Gui'),
                'choices' => [
                    $this->translator->trans('Light', [], 'Modules.Inpostizi.Gui') => false,
                    $this->translator->trans('Dark', [], 'Modules.Inpostizi.Gui') => true,
                ],
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                ],
            ])
            ->add('variant', EnumType::class, [
                'label' => $this->translator->trans('Variant', [], 'Modules.Inpostizi.Gui'),
                'help' => $this->translator->trans('The widget is available in 2 color variants. Choose the one more suitable for your store\'s color scheme.', [], 'Modules.Inpostizi.Gui'),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                ],
                'class' => Variant::class,
            ])
            ->add('frameStyle', EnumType::class, [
                'label' => $this->translator->trans('Frame style', [], 'Modules.Inpostizi.Gui'),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                ],
                'class' => FrameStyle::class,
                'required' => false,
                'placeholder' => $this->translator->trans('Rectangular', [], 'Modules.Inpostizi.Gui'),
            ])
            ->add('size', EnumType::class, [
                'label' => $this->translator->trans('Size', [], 'Modules.Inpostizi.Gui'),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                ],
                'class' => Size::class,
            ])
            ->add('maxWidthPx', IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Max width', [], 'Modules.Inpostizi.Gui'),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                ],
                'unit' => 'px',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => WidgetConfiguration::class,
                'preview_container_styles' => [],
            ])
            ->setAllowedTypes('preview_container_styles', 'iterable');
    }
}
