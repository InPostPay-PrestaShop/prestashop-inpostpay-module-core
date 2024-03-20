<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Configuration\DTO\Shipping\CarrierMapping;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CarrierMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('referenceId', CarrierChoiceType::class, [
            'required' => $options['required'],
            'label' => false,
            'placeholder' => '--',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => CarrierMapping::class,
                'empty_data' => function (Options $options) {
                    return new CarrierMapping(null, $options['service_codes']);
                },
                'service_codes' => [],
            ])
            ->setAllowedTypes('service_codes', ServiceCode::class . '[]');
    }
}
