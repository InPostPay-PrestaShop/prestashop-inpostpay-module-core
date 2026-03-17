<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\DTO\ShippingConfiguration;
use izi\prestashop\Form\Type\Shipping\ShippingOptionsType;
use izi\prestashop\Form\Type\SwitchType as SwitchTypePolyfill;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShippingConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'shippingconfigurationtype';

    private $translator;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(LegacyTranslator $translator, ?\Context $context = null)
    {
        $this->translator = $translator;
        $this->context = $context ?? \Context::getContext();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $switchClass = class_exists(SwitchType::class) ? SwitchType::class : SwitchTypePolyfill::class;

        $builder
            ->add('courierShippingOptions', ShippingOptionsType::class, [
                'label' => $this->translator->l('Courier', self::TRANSLATION_SOURCE),
                'delivery_type' => DeliveryType::Courier(),
            ])
            ->add('apmShippingOptions', ShippingOptionsType::class, [
                'label' => $this->translator->l('Parcel Locker', self::TRANSLATION_SOURCE),
                'delivery_type' => DeliveryType::Apm(),
            ])
            ->add('giftWrappingEnabled', $switchClass, [
                'label' => $this->translator->l('Offer gift wrapping in the mobile app', self::TRANSLATION_SOURCE),
                'help' => \sprintf($this->translator->l('The service will not be available unless the "%s" is enabled in the order settings.', self::TRANSLATION_SOURCE), $this->context->getTranslator()->trans('Offer gift wrapping', [], 'Admin.Shopparameters.Feature')),
                'empty_data' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShippingConfiguration::class,
        ]);
    }
}
