<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Form\DataMapper\ArrayTrimmingDataMapper;
use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\Translation\ServiceNameTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CarrierMappingsType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'carriermappingstype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var ServiceNameTranslator
     */
    private $serviceNameTranslator;

    public function __construct(LegacyTranslator $translator, ServiceNameTranslator $serviceNameTranslator)
    {
        $this->translator = $translator;
        $this->serviceNameTranslator = $serviceNameTranslator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (ServiceCode::getAvailableCombinations($options['delivery_type']) as $serviceCodes) {
            $name = $this->getChildName(...$serviceCodes);

            $builder->add($name, CarrierMappingType::class, [
                'service_codes' => $serviceCodes,
                'label' => $this->getChildLabel(...$serviceCodes),
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

    private function getServiceCodeCombinations(DeliveryType $deliveryType): array
    {
        $combinations = [[]];
        $serviceCodes = $deliveryType->getAvailableServiceCodes();

        foreach ($serviceCodes as $serviceCode) {
            $combinations[] = [$serviceCode];
        }

        while ($serviceCode = array_shift($serviceCodes)) {
            $combination = [$serviceCode];

            foreach ($serviceCodes as $serviceCode) {
                $combination[] = $serviceCode;
            }

            $combinations[] = $combination;
        }

        return $combinations;
    }

    private function getChildName(ServiceCode ...$serviceCodes): string
    {
        if ([] === $serviceCodes) {
            return 'default';
        }

        return implode(':', array_map(static function (ServiceCode $serviceCode): string {
            return $serviceCode->value;
        }, $serviceCodes));
    }

    private function getChildLabel(ServiceCode ...$serviceCodes): string
    {
        $label = $this->translator->l('Carrier mapping', self::TRANSLATION_SOURCE);

        if ([] === $serviceCodes) {
            return $label;
        }

        return sprintf('%s (%s)', $label, implode(' + ', array_map(function (ServiceCode $serviceCode): string {
            return $this->serviceNameTranslator->getName($serviceCode);
        }, $serviceCodes)));
    }
}
