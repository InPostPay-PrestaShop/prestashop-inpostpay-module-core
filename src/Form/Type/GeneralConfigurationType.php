<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommand;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Form\Event\ApiConfigurationValidatedEvent;
use izi\prestashop\Form\Type\SwitchType as SwitchTypePolyfill;
use izi\prestashop\Hook\Front\DisplayCheckoutSummaryTop;
use izi\prestashop\Hook\Front\DisplayIziCheckoutButton;
use izi\prestashop\Hook\Front\DisplayIziThankYou;
use izi\prestashop\Hook\Front\DisplayOrderConfirmation;
use izi\prestashop\Hook\Front\DisplayPaymentReturn;
use izi\prestashop\Hook\Front\DisplayProductActions;
use izi\prestashop\Hook\Front\DisplayProductAdditionalInfo;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;

final class GeneralConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'generalconfigurationtype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var EventDispatcherInterface|null
     */
    private $eventDispatcher;

    public function __construct(LegacyTranslator $translator, ?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $switchClass = class_exists(SwitchType::class)
            ? SwitchType::class
            : SwitchTypePolyfill::class;

        $builder
            ->add('enableForEveryone', ChoiceType::class, [
                'choices' => [
                    $this->translator->l('to everyone', self::TRANSLATION_SOURCE) => true,
                    $this->translator->l('to testers', self::TRANSLATION_SOURCE) => false,
                ],
                'property_path' => 'generalConfiguration.enabledForEveryone',
                'label' => $this->translator->l('Display widget', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('If you select "to testers" the widget will be visible only to those who are supposed to see it. To display the widget in this mode in a web browser, type the address of your store with \'?showIzi=true\' Example: https://mojsklep.pl?showIzi=true', self::TRANSLATION_SOURCE),
            ])
            ->add('thankYouDisplayHook', ChoiceType::class, [
                'choices' => [
                    DisplayPaymentReturn::getHookName() => DisplayPaymentReturn::getHookName(),
                    DisplayOrderConfirmation::getHookName() => DisplayOrderConfirmation::getHookName(),
                    DisplayIziThankYou::getHookName() => DisplayIziThankYou::getHookName(),
                ],
                'property_path' => 'generalConfiguration.thankYouDisplayHook',
                'label' => $this->translator->l('Order confirmation page display hook', self::TRANSLATION_SOURCE),
                'help' => sprintf($this->translator->l('If you choose the \'%s\' hook you have to manually implement it in the templates/checkout/order-confirmation.tpl file \'{hook h="%s" order=$order}\'.', self::TRANSLATION_SOURCE), DisplayIziThankYou::getHookName(), DisplayIziThankYou::getHookName()),
            ])
            ->add('productCardDisplayHook', ChoiceType::class, [
                'choices' => [
                    DisplayProductAdditionalInfo::getHookName() => DisplayProductAdditionalInfo::getHookName(),
                    DisplayProductActions::getHookName() => DisplayProductActions::getHookName(),
                ],
                'property_path' => 'generalConfiguration.productCardDisplayHook',
                'label' => $this->translator->l('Product page hook used to display widget', self::TRANSLATION_SOURCE),
                'help' => sprintf($this->translator->l('You can choose a different hook if you have problems displaying the InPost Pay widget on the product page.', self::TRANSLATION_SOURCE), DisplayIziThankYou::getHookName(), DisplayIziThankYou::getHookName()),
            ])
            ->add('checkoutButtonDisplayHook', ChoiceType::class, [
                'choices' => [
                    DisplayCheckoutSummaryTop::getHookName() => DisplayCheckoutSummaryTop::getHookName(),
                    DisplayIziCheckoutButton::getHookName() => DisplayIziCheckoutButton::getHookName(),
                ],
                'property_path' => 'generalConfiguration.checkoutButtonDisplayHook',
                'label' => $this->translator->l('Checkout process hook used to display widget', self::TRANSLATION_SOURCE),
                'help' => sprintf($this->translator->l('If you choose the \'%s\' hook you have to manually implement it in the template \'{hook h="%s"}\'.', self::TRANSLATION_SOURCE), DisplayIziCheckoutButton::getHookName(), DisplayIziCheckoutButton::getHookName()),
            ])
            ->add('fullPageCacheModuleInUse', $switchClass, [
                'property_path' => 'generalConfiguration.fullPageCacheModuleInUse',
                'label' => $this->translator->l('Full page cache in use', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Use this option if you are using the full page cache module or a varnish, lightspeed cache.', self::TRANSLATION_SOURCE),
            ])
            ->add('sendAnalyticsData', $switchClass, [
                'property_path' => 'generalConfiguration.sendAnalyticsData',
                'label' => $this->translator->l('Send analytics data', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Use this option if you want to send analytics data to InPostPay', self::TRANSLATION_SOURCE),
            ])
            ->add('widgetSplitBoundEnabled', $switchClass, [
                'property_path' => 'generalConfiguration.widgetSplitBoundEnabled',
                'label' => $this->translator->l('Use the split button', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Replaces the standard button on the product card with a split version that offers two actions: add to cart and display information about basket binding.', self::TRANSLATION_SOURCE),
            ])
            ->add('apiConfiguration', ApiConfigurationType::class, [
                'label' => false,
                'error_mapping' => [
                    '.' => 'clientCredentials.clientSecret',
                ],
            ])
            ->add('ordersConfiguration', OrdersConfigurationType::class, [
                'label' => false,
            ])
            ->add('productConfiguration', ProductConfigurationType::class, [
                'label' => $this->translator->l('Product images configuration', self::TRANSLATION_SOURCE),
            ])
            ->add('maxSuggestedProducts', IntegerType::class, [
                'property_path' => 'generalConfiguration.maxSuggestedProducts',
                'required' => false,
                'label' => $this->translator->l('Maximum number of suggested products', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('To show suggested products, complete the Accessories Products section in the product configuration.', self::TRANSLATION_SOURCE),
                'attr' => [
                    'placeholder' => $this->translator->l('unlimited', self::TRANSLATION_SOURCE),
                    'min' => 0,
                ],
            ])
            ->add('defaultPromoDetailsPageId', ObjectModelType::class, [
                'property_path' => 'generalConfiguration.defaultPromoDetailsPageId',
                'required' => false,
                'class' => \CMS::class,
                'input' => 'id',
                'choice_label' => static function (\CMS $page): string {
                    return (string) $page->meta_title;
                },
                'label' => $this->translator->l('Default promotion details page', self::TRANSLATION_SOURCE),
                'placeholder' => '--',
                'help' => nl2br(implode("\n", [
                    $this->translator->l('The page to use as the promotion details link for highlighted discounts if no specific page is configured for the cart rule.', self::TRANSLATION_SOURCE),
                    $this->translator->l('If neither the default value nor the cart rule specific value is configured, the available promotion data will not be passed to the mobile app.', CartRuleOptionsType::TRANSLATION_SOURCE),
                ])),
            ]);

        if (null === $this->eventDispatcher) {
            return;
        }

        $builder
            ->get('apiConfiguration')
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
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
