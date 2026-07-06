<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Common\PaymentType;
use izi\prestashop\Configuration\DTO\OrdersConfiguration;
use izi\prestashop\Form\Type\Order\MessageOptionsType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrdersConfigurationType extends AbstractType
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
            ->add('defaultInitialStatusId', ObjectModelType::class, [
                'label' => $this->translator->trans('Initial order status', [], 'Modules.Inpostizi.Order'),
                'class' => \OrderState::class,
                'input' => 'id',
            ])
            ->add('cashOnDeliveryStatusId', ObjectModelType::class, [
                'label' => \sprintf('%s (%s)', $this->translator->trans('Initial order status', [], 'Modules.Inpostizi.Order'), PaymentType::CashOnDelivery()->trans($this->translator)),
                'class' => \OrderState::class,
                'input' => 'id',
            ])
            ->add('paidStatusId', ObjectModelType::class, [
                'label' => $this->translator->trans('Paid order status', [], 'Modules.Inpostizi.Order'),
                'class' => \OrderState::class,
                'input' => 'id',
            ])
            ->add('statusDescriptionMap', TranslatableType::class, [
                'label' => $this->translator->trans('Order statuses', [], 'Modules.Inpostizi.Order'),
                'type' => OrderStatusDescriptionMapType::class,
            ])
            ->add('pointOfSaleId', TextType::class, [
                'label' => $this->translator->trans('POS ID', [], 'Modules.Inpostizi.Order'),
                'help' => $this->translator->trans('To obtain a value for the sandbox environment, please contact InPost. Production environment value can be obtained in the merchant panel.', [], 'Modules.Inpostizi.Environment'),
            ])
            ->add('messageOptions', MessageOptionsType::class, [
                'label' => $this->translator->trans('Order message', [], 'Modules.Inpostizi.Order'),
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
