<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Form\DataMapper\ArrayTrimmingDataMapper;
use izi\prestashop\Translation\ServiceNameTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OptionalServicesType extends AbstractType
{
    /**
     * @var ServiceNameTranslator
     */
    private $serviceNameTranslator;

    public function __construct(ServiceNameTranslator $serviceNameTranslator)
    {
        $this->serviceNameTranslator = $serviceNameTranslator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var DeliveryType $deliveryType */
        $deliveryType = $options['delivery_type'];

        foreach ($deliveryType->getAvailableServiceCodes() as $serviceCode) {
            $name = $serviceCode->value;

            $builder->add($name, ServiceOptionsType::class, [
                'service_code' => $serviceCode,
                'label' => $this->serviceNameTranslator->getName($serviceCode),
            ]);
        }

        $builder->setDataMapper(new ArrayTrimmingDataMapper());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'delivery_type',
            ])
            ->setAllowedTypes('delivery_type', DeliveryType::class);
    }
}
