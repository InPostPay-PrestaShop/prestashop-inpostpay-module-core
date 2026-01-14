<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\DTO\ShippingConfiguration;
use izi\prestashop\Form\Type\Shipping\ShippingOptionsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ShippingConfigurationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('courierShippingOptions', ShippingOptionsType::class, [
                'label' => DeliveryType::Courier()->trans($this->translator),
                'delivery_type' => DeliveryType::Courier(),
            ])
            ->add('apmShippingOptions', ShippingOptionsType::class, [
                'label' => DeliveryType::Apm()->trans($this->translator),
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
