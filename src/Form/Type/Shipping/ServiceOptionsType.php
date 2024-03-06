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

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('additionalCost', MoneyType::class, [
            'required' => false,
            'currency' => Currency::Pln()->value,
            'scale' => 2,
            'label' => $this->translator->l('Additional cost', self::TRANSLATION_SOURCE),
            'help' => $this->translator->l('Net value of the amount to be added to the carrier\'s price if the service is selected.', self::TRANSLATION_SOURCE),
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
