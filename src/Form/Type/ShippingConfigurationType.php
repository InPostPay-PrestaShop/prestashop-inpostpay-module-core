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
                'required' => false,
                'shippingIdLabel' => $this->module->l('Kurier', self::TRANSLATION_SOURCE),
                'shippingPriceLabel' => $this->module->l('Kurier paczka w weekend netto', self::TRANSLATION_SOURCE),
                'shippingAvailableFromDayLabel' => $this->module->l('Kurier paczka w weekend dostępne od dnia', self::TRANSLATION_SOURCE),
                'shippingAvailableToDayLabel' => $this->module->l('Kurier paczka w weekend dostępne do dnia', self::TRANSLATION_SOURCE),
                'shippingAvailableFromHourLabel' => $this->module->l('Kurier paczka w weekend dostępne od godziny', self::TRANSLATION_SOURCE),
                'shippingAvailableToHourLabel' => $this->module->l('Kurier paczka w weekend dostępne do godziny', self::TRANSLATION_SOURCE),
                'shippingCodPriceLabel' => $this->module->l('Kurier pobranie netto', self::TRANSLATION_SOURCE),
                'shippingCodAvailableFromDayLabel' => $this->module->l('Kurier pobranie dostępne od dnia', self::TRANSLATION_SOURCE),
                'shippingCodAvailableToDayLabel' => $this->module->l('Kurier pobranie dostępne do dnia', self::TRANSLATION_SOURCE),
                'shippingCodAvailableFromHourLabel' => $this->module->l('Kurier pobranie dostępne od godziny', self::TRANSLATION_SOURCE),
                'shippingCodAvailableToHourLabel' => $this->module->l('Kurier pobranie dostępne do godziny', self::TRANSLATION_SOURCE),
            ])
            ->add('shippingAmp', ShippingType::class, [
                'required' => false,
                'shippingIdLabel' => $this->module->l('Paczkomat', self::TRANSLATION_SOURCE),
                'shippingPriceLabel' => $this->module->l('Paczkomat paczka w weekend netto', self::TRANSLATION_SOURCE),
                'shippingAvailableFromDayLabel' => $this->module->l('Paczkomat paczka w weekend dostępne od dnia', self::TRANSLATION_SOURCE),
                'shippingAvailableToDayLabel' => $this->module->l('Paczkomat paczka w weekend dostępne do dnia', self::TRANSLATION_SOURCE),
                'shippingAvailableFromHourLabel' => $this->module->l('Paczkomat paczka w weekend dostępne od godziny', self::TRANSLATION_SOURCE),
                'shippingAvailableToHourLabel' => $this->module->l('Paczkomat paczka w weekend dostępne do godziny', self::TRANSLATION_SOURCE),
                'shippingCodPriceLabel' => $this->module->l('Paczkomat pobranie netto', self::TRANSLATION_SOURCE),
                'shippingCodAvailableFromDayLabel' => $this->module->l('Paczkomat pobranie dostępne od dnia', self::TRANSLATION_SOURCE),
                'shippingCodAvailableToDayLabel' => $this->module->l('Paczkomat pobranie dostępne do dnia', self::TRANSLATION_SOURCE),
                'shippingCodAvailableFromHourLabel' => $this->module->l('Paczkomat pobranie dostępne od godziny', self::TRANSLATION_SOURCE),
                'shippingCodAvailableToHourLabel' => $this->module->l('Paczkomat pobranie dostępne do godziny', self::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateShippingConfigurationCommand::class,
        ]);
    }
}
