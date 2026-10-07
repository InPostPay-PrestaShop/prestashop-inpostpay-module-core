<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ShippingOptionsType extends AbstractType
{
    /**
     * @var TranslatorInterface|null
     */
    private $translator;

    public function __construct(?TranslatorInterface $translator = null)
    {
        if (null === $translator) {
            @trigger_error(\sprintf('Not passing a $translator to "%s()" is deprecated since version 3.4.0.', __METHOD__), \E_USER_DEPRECATED);
        }

        $this->translator = $translator ?? new IdentityTranslator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('carrierMappings', CarrierMappingsType::class, [
                'delivery_type' => $options['delivery_type'],
                'label' => false,
            ])
            ->add('optionalServices', OptionalServicesType::class, [
                'delivery_type' => $options['delivery_type'],
                'label' => false,
            ])
            ->add('estimatedDeliveryTime', IntegerType::class, [
                'label' => $this->translator->trans('Estimated delivery time', [], 'Modules.Inpostizi.Shipping'),
                'empty_data' => 0,
                'unit' => 'h',
                'attr' => ['min' => 1],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => ShippingOptions::class,
            ])
            ->setRequired([
                'delivery_type',
            ])
            ->setAllowedTypes('delivery_type', DeliveryType::class);
    }
}
