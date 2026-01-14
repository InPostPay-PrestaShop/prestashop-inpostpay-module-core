<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Configuration\DTO\Shipping\CarrierMapping;
use izi\prestashop\Enum\Enum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CarrierMappingsType extends AbstractType implements DataMapperInterface
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
        foreach (ServiceCode::getAvailableCombinations($options['delivery_type']) as $serviceCodes) {
            $name = $this->getChildName(...$serviceCodes);

            $builder->add($name, CarrierMappingType::class, [
                'service_codes' => $serviceCodes,
                'label' => $this->getChildLabel(...$serviceCodes),
            ]);
        }

        $builder->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'delivery_type',
            ])
            ->setAllowedTypes('delivery_type', DeliveryType::class);
    }

    public function mapDataToForms($viewData, $forms): void
    {
        if (null === $viewData) {
            return;
        }

        if (!\is_array($viewData)) {
            throw new UnexpectedTypeException($viewData, 'array');
        }

        foreach ($forms as $form) {
            $data = $this->getCarrierMappingByServiceCodes(
                $viewData,
                $form->getConfig()->getOption('service_codes')
            );

            $form->setData($data);
        }
    }

    public function mapFormsToData($forms, &$viewData): void
    {
        $viewData = [];

        foreach ($forms as $form) {
            $viewData[] = $form->getData();
        }
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
        $label = $this->translator->trans('Carrier mapping', [], 'Modules.Inpostizi.Shipping');

        if ([] === $serviceCodes) {
            return $label;
        }

        return \sprintf('%s (%s)', $label, implode(' + ', array_map(function (ServiceCode $serviceCode): string {
            return $serviceCode->trans($this->translator);
        }, $serviceCodes)));
    }

    /**
     * @param CarrierMapping[] $mappings
     * @param ServiceCode[] $serviceCodes
     */
    private function getCarrierMappingByServiceCodes(array $mappings, array $serviceCodes): ?CarrierMapping
    {
        foreach ($mappings as $mapping) {
            $mappingServiceCodes = $mapping->getServiceCodes();

            $diff = array_udiff($serviceCodes, $mappingServiceCodes, [Enum::class, 'compareValues']);

            if ([] === $diff && \count($mappingServiceCodes) === \count($serviceCodes)) {
                return $mapping;
            }
        }

        return null;
    }
}
