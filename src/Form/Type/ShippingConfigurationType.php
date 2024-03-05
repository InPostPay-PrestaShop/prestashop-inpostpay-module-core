<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\DTO\ShippingConfiguration;
use izi\prestashop\Form\Type\Shipping\ShippingOptionsType;
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
            ->add('courierShippingOptions', ShippingOptionsType::class, [
                'label' => $this->translator->l('Courier', self::TRANSLATION_SOURCE),
                'delivery_type' => DeliveryType::Courier(),
            ])
            ->add('apmShippingOptions', ShippingOptionsType::class, [
                'label' => $this->translator->l('Parcel Locker', self::TRANSLATION_SOURCE),
                'delivery_type' => DeliveryType::Apm(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShippingConfiguration::class,
        ]);
    }
}
