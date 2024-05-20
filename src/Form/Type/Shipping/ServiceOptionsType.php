<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Configuration\DTO\Shipping\ServiceOptions;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ServiceOptionsType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'serviceoptionstype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(LegacyTranslator $translator, \Context $context = null)
    {
        $this->translator = $translator;
        $this->context = $context ?? \Context::getContext();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('additionalCost', MoneyType::class, [
            'required' => false,
            'currency' => Currency::Pln()->value,
            'scale' => 2,
            'label' => $this->translator->l('Additional cost', self::TRANSLATION_SOURCE),
            'help' => nl2br(implode("\n", [
                $this->translator->l('Net value of the amount to be added to the carrier\'s price if the service is selected.', self::TRANSLATION_SOURCE),
                sprintf($this->translator->l('Cost will not be applied if the "%s" option is not enabled for the carrier.', self::TRANSLATION_SOURCE), $this->context->getTranslator()->trans('Add handling costs', [], 'Admin.Shipping.Feature')),
            ])),
        ]);

        /** @var ServiceCode $serviceCode */
        $serviceCode = $options['service_code'];

        if (!$serviceCode->isAvailabilityTimeDependent()) {
            return;
        }

        $builder->add('availabilityRange', TimeOfWeekRangeType::class, [
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => ServiceOptions::class,
                'empty_data' => function (Options $options) {
                    return new ServiceOptions($options['service_code']);
                },
            ])
            ->setRequired(['service_code'])
            ->setAllowedTypes('service_code', ServiceCode::class);
    }
}
