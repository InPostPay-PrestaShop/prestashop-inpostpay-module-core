<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\Shipping;
use izi\prestashop\Form\Type\Shipping\CarrierChoiceType;
use izi\prestashop\Form\Type\Shipping\DayChoiceType;
use izi\prestashop\Form\Type\Shipping\HourChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShippingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('carrierId', CarrierChoiceType::class, [
                'required' => false,
                'label' => $options['shippingIdLabel'],
                'empty_data' => null,
            ])
            ->add('shippingPrice', MoneyCurrencyType::class, [
                'required' => false,
                'label' => $options['shippingPriceLabel'],
            ])
            ->add('shippingAvailableFromDay', DayChoiceType::class, [
                'required' => false,
                'label' => $options['shippingAvailableFromDayLabel'],
            ])
            ->add('shippingAvailableToDay', DayChoiceType::class, [
                'required' => false,
                'label' => $options['shippingAvailableToDayLabel'],
            ])
            ->add('shippingAvailableFromHour', HourChoiceType::class, [
                'required' => false,
                'label' => $options['shippingAvailableFromHourLabel'],
            ])
            ->add('shippingAvailableToHour', HourChoiceType::class, [
                'required' => false,
                'label' => $options['shippingAvailableToHourLabel'],
            ])
            ->add('shippingCodPrice', MoneyCurrencyType::class, [
                'required' => false,
                'label' => $options['shippingCodPriceLabel'],
            ])
            ->add('shippingCodAvailableFromDay', DayChoiceType::class, [
                'required' => false,
                'label' => $options['shippingCodAvailableFromDayLabel'],
            ])
            ->add('shippingCodAvailableToDay', DayChoiceType::class, [
                'required' => false,
                'label' => $options['shippingCodAvailableToDayLabel'],
            ])
            ->add('shippingCodAvailableFromHour', HourChoiceType::class, [
                'required' => false,
                'label' => $options['shippingCodAvailableFromHourLabel'],
            ])
            ->add('shippingCodAvailableToHour', HourChoiceType::class, [
                'required' => false,
                'label' => $options['shippingCodAvailableToHourLabel'],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'shippingIdLabel',
            'shippingPriceLabel',
            'shippingAvailableFromDayLabel',
            'shippingAvailableToDayLabel',
            'shippingAvailableFromHourLabel',
            'shippingAvailableToHourLabel',
            'shippingCodPriceLabel',
            'shippingCodAvailableFromDayLabel',
            'shippingCodAvailableToDayLabel',
            'shippingCodAvailableFromHourLabel',
            'shippingCodAvailableToHourLabel',
        ]);

        $resolver->setDefaults([
            'data_class' => Shipping::class,
        ]);

        $resolver->setAllowedTypes('shippingIdLabel', 'string');
        $resolver->setAllowedTypes('shippingPriceLabel', 'string');
        $resolver->setAllowedTypes('shippingAvailableFromDayLabel', 'string');
        $resolver->setAllowedTypes('shippingAvailableToDayLabel', 'string');
        $resolver->setAllowedTypes('shippingAvailableFromHourLabel', 'string');
        $resolver->setAllowedTypes('shippingAvailableToHourLabel', 'string');
        $resolver->setAllowedTypes('shippingCodPriceLabel', 'string');
        $resolver->setAllowedTypes('shippingCodAvailableFromDayLabel', 'string');
        $resolver->setAllowedTypes('shippingCodAvailableToDayLabel', 'string');
        $resolver->setAllowedTypes('shippingCodAvailableFromHourLabel', 'string');
        $resolver->setAllowedTypes('shippingCodAvailableToHourLabel', 'string');
    }
}
