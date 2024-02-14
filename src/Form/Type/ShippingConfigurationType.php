<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\ShippingConfiguration;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShippingConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'shippingconfigurationtype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('courierShippingOptions', ShippingType::class, [
                'label' => $this->translator->l('Courier', self::TRANSLATION_SOURCE),
                'required' => false,
                'shippingIdLabel' => $this->translator->l('Courier', self::TRANSLATION_SOURCE),
                'shippingPriceLabel' => $this->translator->l('Courier package weekend net price', self::TRANSLATION_SOURCE),
                'shippingCodPriceLabel' => $this->translator->l('Courier COD net price', self::TRANSLATION_SOURCE),
            ])
            ->add('apmShippingOptions', ShippingType::class, [
                'label' => $this->translator->l('Paczkomat', self::TRANSLATION_SOURCE),
                'required' => false,
                'shippingIdLabel' => $this->translator->l('Paczkomat', self::TRANSLATION_SOURCE),
                'shippingPriceLabel' => $this->translator->l('Paczkomat package weekend net price', self::TRANSLATION_SOURCE),
                'shippingCodPriceLabel' => $this->translator->l('Paczkomat package COD net price', self::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShippingConfiguration::class,
        ]);
    }
}
