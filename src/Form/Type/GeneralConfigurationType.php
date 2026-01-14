<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommand;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Form\Event\ApiConfigurationValidatedEvent;
use izi\prestashop\Hook\Front\DisplayCheckoutSummaryTop;
use izi\prestashop\Hook\Front\DisplayIziCheckoutButton;
use izi\prestashop\Hook\Front\DisplayIziThankYou;
use izi\prestashop\Hook\Front\DisplayOrderConfirmation;
use izi\prestashop\Hook\Front\DisplayPaymentReturn;
use izi\prestashop\Hook\Front\DisplayProductActions;
use izi\prestashop\Hook\Front\DisplayProductAdditionalInfo;
use izi\prestashop\Security\Voter\BindingWidgetVoter;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GeneralConfigurationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $eventDispatcher)
    {
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('apiConfiguration', ApiConfigurationType::class, [
                'label' => false,
                'error_mapping' => [
                    '.' => 'clientCredentials.clientSecret',
                ],
            ])
            ->add('enableForEveryone', ChoiceType::class, [
                'property_path' => 'generalConfiguration.enabledForEveryone',
                'choices' => [
                    $this->translator->trans('to everyone', [], 'Modules.Inpostizi.General') => true,
                    $this->translator->trans('to testers', [], 'Modules.Inpostizi.General') => false,
                ],
                'label' => $this->translator->trans('Display widget', [], 'Modules.Inpostizi.General'),
                'help' => $this->translator->trans('If the "{option}" option is selected, the widget will not be visible to users that have not visited your store with the "{query_param}" query parameter, (e.g. https://my-store.com?{query_param}).', [
                    '{option}' => $this->translator->trans('to testers', [], 'Modules.Inpostizi.General'),
                    '{query_param}' => \sprintf('%s=%s', BindingWidgetVoter::QUERY_PARAM_NAME, BindingWidgetVoter::QUERY_PARAM_VALUE),
                ], 'Modules.Inpostizi.General'),
            ])
            ->add('thankYouDisplayHook', ChoiceType::class, [
                'property_path' => 'generalConfiguration.thankYouDisplayHook',
                'choices' => [
                    DisplayPaymentReturn::HOOK_NAME => DisplayPaymentReturn::HOOK_NAME,
                    DisplayOrderConfirmation::HOOK_NAME => DisplayOrderConfirmation::HOOK_NAME,
                    DisplayIziThankYou::HOOK_NAME => DisplayIziThankYou::HOOK_NAME,
                ],
                'label' => $this->translator->trans('Order confirmation page widget display hook', [], 'Modules.Inpostizi.General'),
                'help' => $this->translator->trans('If you choose the "{hook}" hook, the hook call must be manually added in the {template} file: `{code}.`', [
                    '{template}' => 'templates/checkout/order-confirmation.tpl',
                    '{hook}' => DisplayIziThankYou::HOOK_NAME,
                    '{code}' => \sprintf('{hook h="%s" order=$order}', DisplayIziThankYou::HOOK_NAME),
                ], 'Modules.Inpostizi.General'),
            ])
            ->add('productCardDisplayHook', ChoiceType::class, [
                'property_path' => 'generalConfiguration.productCardDisplayHook',
                'choices' => [
                    DisplayProductAdditionalInfo::HOOK_NAME => DisplayProductAdditionalInfo::HOOK_NAME,
                    DisplayProductActions::HOOK_NAME => DisplayProductActions::HOOK_NAME,
                ],
                'label' => $this->translator->trans('Product page widget display hook', [], 'Modules.Inpostizi.General'),
                'help' => $this->translator->trans('You can choose a different hook if you have problems displaying the InPost Pay widget on the product page.', [], 'Modules.Inpostizi.General'),
            ])
            ->add('checkoutButtonDisplayHook', ChoiceType::class, [
                'property_path' => 'generalConfiguration.checkoutButtonDisplayHook',
                'choices' => [
                    DisplayCheckoutSummaryTop::HOOK_NAME => DisplayCheckoutSummaryTop::HOOK_NAME,
                    DisplayIziCheckoutButton::HOOK_NAME => DisplayIziCheckoutButton::HOOK_NAME,
                ],
                'label' => $this->translator->trans('Checkout page widget display hook', [], 'Modules.Inpostizi.General'),
                'help' => $this->translator->trans('If you choose the "{hook}" hook, the hook call must be manually added in the template file: `{code}.`', [
                    '{hook}' => DisplayIziCheckoutButton::HOOK_NAME,
                    '{code}' => \sprintf('{hook h="%s"}', DisplayIziCheckoutButton::HOOK_NAME),
                ], 'Modules.Inpostizi.General'),
            ])
            ->add('fullPageCacheModuleInUse', SwitchType::class, [
                'property_path' => 'generalConfiguration.fullPageCacheModuleInUse',
                'label' => $this->translator->trans('Full page cache in use', [], 'Modules.Inpostizi.General'),
                'help' => $this->translator->trans('Use this option if you are using a full page cache module or other caching tools such as Varnish, LiteSpeed Cache etc.', [], 'Modules.Inpostizi.General'),
            ])
            ->add('sendAnalyticsData', SwitchType::class, [
                'property_path' => 'generalConfiguration.sendAnalyticsData',
                'label' => $this->translator->trans('Send analytics data', [], 'Modules.Inpostizi.General'),
                'help' => $this->translator->trans('If enabled, the values of the customer\'s tracking cookies will be added to the order data sent to InPost Pay.', [], 'Modules.Inpostizi.General'),
            ])
            ->add('ordersConfiguration', OrdersConfigurationType::class, [
                'label' => false,
            ])
            ->add('productConfiguration', ProductConfigurationType::class, [
                'label' => $this->translator->trans('Product images configuration', [], 'Modules.Inpostizi.General'),
            ])
            ->add('maxSuggestedProducts', IntegerType::class, [
                'property_path' => 'generalConfiguration.maxSuggestedProducts',
                'required' => false,
                'label' => $this->translator->trans('Maximum number of suggested products', [], 'Modules.Inpostizi.General'),
                'help' => $this->translator->trans('The suggested products are selected based on the settings in the "{section}" section of the product configuration form.', [
                    '{section}' => $this->translator->trans('Related products', [], 'Admin.Catalog.Feature'),
                ], 'Modules.Inpostizi.General'),
                'attr' => [
                    'placeholder' => $this->translator->trans('unlimited', [], 'Modules.Inpostizi.General'),
                    'min' => 0,
                ],
            ])
            ->add('defaultPromoDetailsPageId', CmsPageChoiceType::class, [
                'property_path' => 'generalConfiguration.defaultPromoDetailsPageId',
                'required' => false,
                'input' => 'id',
                'label' => $this->translator->trans('Default promotion details page', [], 'Modules.Inpostizi.Promo'),
                'placeholder' => '--',
                'help' => nl2br(implode("\n", [
                    $this->translator->trans('The page to use as the promotion details link for highlighted discounts if no specific page is configured for the cart rule.', [], 'Modules.Inpostizi.Promo'),
                    $this->translator->trans('If neither the default value nor the cart rule specific value is configured, the available promotion data will not be passed to the mobile app.', [], 'Modules.Inpostizi.Promo'),
                ])),
            ]);

        $builder->get('apiConfiguration')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            if (!$event->getForm()->isValid()) {
                return;
            }

            $this->eventDispatcher->dispatch(new ApiConfigurationValidatedEvent($event->getData()));
        }, -100);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateGeneralConfigurationCommand::class,
            'validation_groups' => new GroupSequence(['Default', 'API']),
        ]);
    }
}
