<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\GuiConfiguration;
use izi\prestashop\Form\Type\Widget\WidgetConfigurationType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
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
            ->add('widgetDisplayedOnCartPage', SwitchType::class, [
                'required' => false,
                'label' => $this->module->l('Displayed', self::TRANSLATION_SOURCE),
            ])
            ->add('cartPageWidgetConfiguration', WidgetConfigurationType::class, [
                'label' => $this->module->l('Cart page', self::TRANSLATION_SOURCE),
            ])
            ->add('cartPageHtmlStyles', HtmlStylesType::class, [
                'label' => false,
            ])
            ->add('widgetDisplayedOnProductCard', SwitchType::class, [
                'required' => false,
                'label' => $this->module->l('Displayed', self::TRANSLATION_SOURCE),
            ])
            ->add('productCardWidgetConfiguration', WidgetConfigurationType::class, [
                'label' => $this->module->l('Product card', self::TRANSLATION_SOURCE),
            ])
            ->add('productCardHtmlStyles', HtmlStylesType::class, [
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GuiConfiguration::class,
        ]);
    }
}
