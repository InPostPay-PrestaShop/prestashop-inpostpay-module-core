<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Configuration\DTO\Shipping\ServiceOptions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OptionalServicesType extends AbstractType implements DataMapperInterface
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
        /** @var DeliveryType $deliveryType */
        $deliveryType = $options['delivery_type'];

        foreach ($deliveryType->getAvailableServiceCodes() as $serviceCode) {
            $name = $serviceCode->value;

            $builder->add($name, ServiceOptionsType::class, [
                'service_code' => $serviceCode,
                'label' => $serviceCode->trans($this->translator),
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
            $data = $this->getServiceOptionsByCode(
                $viewData,
                $form->getConfig()->getOption('service_code')
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

    /**
     * @param ServiceOptions[] $options
     */
    private function getServiceOptionsByCode(array $options, ServiceCode $serviceCode): ?ServiceOptions
    {
        foreach ($options as $serviceOptions) {
            if ($serviceCode === $serviceOptions->getServiceCode()) {
                return $serviceOptions;
            }
        }

        return null;
    }
}
