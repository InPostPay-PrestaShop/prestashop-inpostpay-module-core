<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Configuration\DTO\WidgetDisplayConfiguration;
use izi\prestashop\Form\Type\SwitchType as SwitchTypePolyfill;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WidgetDisplayConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'widgetdisplayconfigurationtype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['description'] = $options['description'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $switchClass = class_exists(SwitchType::class)
            ? SwitchType::class
            : SwitchTypePolyfill::class;

        $builder
            ->add('displayed', $switchClass, [
                'required' => false,
                'label' => $this->translator->l('Displayed', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('In order to increase conversions, we recommend displaying InPost Pay on both the shopping cart tab and the product tab.', self::TRANSLATION_SOURCE),
            ])
            ->add('widgetConfiguration', WidgetConfigurationType::class, [
                'label' => $this->translator->l('Cart page', self::TRANSLATION_SOURCE),
            ])
            ->add('htmlStyles', HtmlStylesType::class, [
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'description',
        ]);
        $resolver->setAllowedTypes('description', 'string');

        $resolver->setDefaults([
            'data_class' => WidgetDisplayConfiguration::class,
            'description' => '',
        ]);
    }
}
