<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Command\Config\UpdateShippingConfigurationCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShippingConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'shippingconfigurationtype';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingCourier', ShippingType::class, [
                'label' => $this->module->l('Courier', self::TRANSLATION_SOURCE),
                'required' => false,
                'shippingIdLabel' => $this->module->l('Courier', self::TRANSLATION_SOURCE),
                'shippingPriceLabel' => $this->module->l('Courier package weekend net price', self::TRANSLATION_SOURCE),
                'shippingCodPriceLabel' => $this->module->l('Courier COD net price', self::TRANSLATION_SOURCE),
            ])
            ->add('shippingAmp', ShippingType::class, [
                'label' => $this->module->l('Paczkomat', self::TRANSLATION_SOURCE),
                'required' => false,
                'shippingIdLabel' => $this->module->l('Paczkomat', self::TRANSLATION_SOURCE),
                'shippingPriceLabel' => $this->module->l('Paczkomat package weekend net price', self::TRANSLATION_SOURCE),
                'shippingCodPriceLabel' => $this->module->l('Paczkomat package COD net price', self::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateShippingConfigurationCommand::class,
        ]);
    }
}
