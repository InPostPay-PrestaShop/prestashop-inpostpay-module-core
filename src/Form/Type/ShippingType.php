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
            ])
            ->add('shippingAvailableToDay', DayChoiceType::class, [
                'required' => false,
            ])
            ->add('shippingAvailableFromHour', HourChoiceType::class, [
                'required' => false,
            ])
            ->add('shippingAvailableToHour', HourChoiceType::class, [
                'required' => false,
            ])
            ->add('shippingCodPrice', MoneyCurrencyType::class, [
                'required' => false,
                'label' => $options['shippingCodPriceLabel'],
            ])
            ->add('shippingCodAvailableFromDay', DayChoiceType::class, [
                'required' => false,
            ])
            ->add('shippingCodAvailableToDay', DayChoiceType::class, [
                'required' => false,
            ])
            ->add('shippingCodAvailableFromHour', HourChoiceType::class, [
                'required' => false,
            ])
            ->add('shippingCodAvailableToHour', HourChoiceType::class, [
                'required' => false,
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'shippingIdLabel',
            'shippingPriceLabel',
            'shippingCodPriceLabel',
        ]);

        $resolver->setDefaults([
            'data_class' => Shipping::class,
        ]);

        $resolver->setAllowedTypes('shippingIdLabel', 'string');
        $resolver->setAllowedTypes('shippingPriceLabel', 'string');
        $resolver->setAllowedTypes('shippingCodPriceLabel', 'string');
    }
}
