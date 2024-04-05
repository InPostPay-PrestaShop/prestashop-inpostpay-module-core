<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\GuiConfiguration;
use izi\prestashop\Form\Type\Widget\WidgetDisplayConfiguration;
use izi\prestashop\Form\Type\Widget\WidgetDisplayConfigurationType;
use izi\prestashop\Translation\LegacyTranslator;
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
        $builder
            ->add('cartWidgetDisplayConfiguration', WidgetDisplayConfigurationType::class, [
                'label' => $this->translator->l('Cart page', self::TRANSLATION_SOURCE),
            ])
            ->add('productWidgetDisplayConfiguration', WidgetDisplayConfigurationType::class, [
                'label' => $this->translator->l('Product card', self::TRANSLATION_SOURCE),
            ])
            ->add('loginPageWidgetDisplayConfiguration', WidgetDisplayConfigurationType::class, [
                'label' => $this->translator->l('Login page', self::TRANSLATION_SOURCE),
            ])
            ->add('registerFormPageWidgetDisplayConfiguration', WidgetDisplayConfigurationType::class, [
                'label' => $this->translator->l('Register page', self::TRANSLATION_SOURCE),
            ])
            ->add('checkoutPageWidgetDisplayConfiguration', WidgetDisplayConfigurationType::class, [
                'label' => $this->translator->l('Checkout page', self::TRANSLATION_SOURCE),
            ])
            ->add('miniCartPageWidgetDisplayConfiguration', WidgetDisplayConfigurationType::class, [
                'label' => $this->translator->l('Cart preview', self::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GuiConfiguration::class,
        ]);
    }
}
