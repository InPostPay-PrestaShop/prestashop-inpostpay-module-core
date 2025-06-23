<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\OrdersConfiguration;
use izi\prestashop\Form\Type\Order\AvailablePaymentOptionsChoiceType;
use izi\prestashop\Form\Type\Order\MessageOptionsType;
use izi\prestashop\Form\Type\SwitchType as SwitchTypePolyfill;
use izi\prestashop\Form\Type\TranslatableType as TranslatableTypePolyfill;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrdersConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'ordersconfigurationtype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translatableClass = class_exists(TranslatableType::class) ? TranslatableType::class : TranslatableTypePolyfill::class;
        $switchClass = class_exists(SwitchType::class) ? SwitchType::class : SwitchTypePolyfill::class;

        $builder
            ->add('defaultInitialStatusId', OrderStateChoiceType::class, [
                'label' => $this->translator->l('Initial order status', self::TRANSLATION_SOURCE),
            ])
            ->add('cashOnDeliveryStatusId', OrderStateChoiceType::class, [
                'label' => $this->translator->l('Initial order status (cash on delivery)', self::TRANSLATION_SOURCE),
            ])
            ->add('paidStatusId', OrderStateChoiceType::class, [
                'label' => $this->translator->l('Paid order status', self::TRANSLATION_SOURCE),
            ])
            ->add('statusDescriptionMap', $translatableClass, [
                'label' => $this->translator->l('Order statuses', self::TRANSLATION_SOURCE),
                'type' => OrderStatusDescriptionMapType::class,
            ])
            ->add('allPaymentOptionsEnabled', $switchClass, [
                'required' => false,
                'label' => $this->translator->l('Enable all available payment options', self::TRANSLATION_SOURCE),
                'help' => nl2br(implode("\n\n", [
                    $this->translator->l('Payment methods are specified on the payment gateway contract', self::TRANSLATION_SOURCE),
                    $this->translator->l('Payment on delivery will be available only if you have an agreement with InPost to provide this service in your store.', self::TRANSLATION_SOURCE),
                ])),
            ])
            ->add('availablePaymentOptions', AvailablePaymentOptionsChoiceType::class, [
                'required' => false,
                'label' => $this->translator->l('Enabled payment options', self::TRANSLATION_SOURCE),
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('pointOfSaleId', TextType::class, [
                'label' => $this->translator->l('POS ID', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('For sandbox environment contact InPost. In the case of a production environment - log into InPost and get the POS ID', self::TRANSLATION_SOURCE),
            ])
            ->add('messageOptions', MessageOptionsType::class, [
                'label' => $this->translator->l('Order message', self::TRANSLATION_SOURCE),
                'error_mapping' => [
                    '.' => 'message',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrdersConfiguration::class,
        ]);
    }
}
