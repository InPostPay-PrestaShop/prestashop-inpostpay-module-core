<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\GuiConfiguration;
use izi\prestashop\Form\Type\Widget\WidgetConfigurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GuiConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'guiconfigurationtype';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('widgetDisplayedOnCartPage', CheckboxType::class, [
                'required' => false,
                'label' => $this->module->l('Displayed', self::TRANSLATION_SOURCE),
            ])
            ->add('cartPageWidgetConfiguration', WidgetConfigurationType::class, [
                'label' => $this->module->l('Cart page', self::TRANSLATION_SOURCE),
            ])
            ->add('cartPageHtmlStyles', HtmlStylesType::class, [
                'label' => false,
            ])
            ->add('widgetDisplayedOnProductCard', CheckboxType::class, [
                'required' => false,
                'label' => $this->module->l('Displayed', self::TRANSLATION_SOURCE),
            ])
            ->add('productCardWidgetConfiguration', WidgetConfigurationType::class, [
                'label' => $this->module->l('Product card', self::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GuiConfiguration::class,
        ]);
    }
}
