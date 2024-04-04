<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\GuiConfiguration;
use izi\prestashop\Form\Type\SwitchType as SwitchTypePolyfill;
use izi\prestashop\Form\Type\Widget\WidgetConfigurationType;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GuiConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'guiconfigurationtype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $switchClass = class_exists(SwitchType::class)
            ? SwitchType::class
            : SwitchTypePolyfill::class;

        $builder
            ->add('widgetDisplayedOnCartPage', $switchClass, [
                'required' => false,
                'label' => $this->translator->l('Displayed', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('In order to increase conversions, we recommend displaying InPost Pay on both the shopping cart tab and the product tab.', self::TRANSLATION_SOURCE),
            ])
            ->add('cartPageWidgetConfiguration', WidgetConfigurationType::class, [
                'label' => $this->translator->l('Cart page', self::TRANSLATION_SOURCE),
            ])
            ->add('cartPageHtmlStyles', HtmlStylesType::class, [
                'label' => false,
            ])
            ->add('widgetDisplayedOnProductCard', $switchClass, [
                'required' => false,
                'label' => $this->translator->l('Displayed', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('In order to increase conversions, we recommend displaying InPost Pay on both the shopping cart tab and the product tab.', self::TRANSLATION_SOURCE),
            ])
            ->add('productCardWidgetConfiguration', WidgetConfigurationType::class, [
                'label' => $this->translator->l('Product card', self::TRANSLATION_SOURCE),
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
