<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\OrdersConfiguration;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrdersConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'ordersconfigurationtype';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('initialStatusId', OrderStateChoiceType::class, [
                'label' => $this->module->l('Initial order status', self::TRANSLATION_SOURCE),
            ])
            ->add('paidStatusId', OrderStateChoiceType::class, [
                'label' => $this->module->l('Paid order status', self::TRANSLATION_SOURCE),
            ])
            // TODO: polyfill type for PS < 1.7.6
            ->add('statusDescriptionMap', TranslatableType::class, [
                'label' => $this->module->l('Order statuses', self::TRANSLATION_SOURCE),
                'type' => OrderStatusDescriptionMapType::class,
            ])
            ->add('bankPaymentEnabled', CheckboxType::class, [
                'required' => false,
                'label' => $this->module->l('Enable payment options according to an agreement with Aion Bank', self::TRANSLATION_SOURCE),
            ])
            ->add('carrierPaymentEnabled', CheckboxType::class, [
                'required' => false,
                'label' => $this->module->l('Enable payment options according to an agreement with InPost', self::TRANSLATION_SOURCE),
            ])
            ->add('pointOfSaleId', TextType::class, [
                'label' => $this->module->l('POS ID', self::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrdersConfiguration::class,
        ]);
    }
}
