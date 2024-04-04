<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommand;
use izi\prestashop\Hook\Front\DisplayIziThankYou;
use izi\prestashop\Hook\Front\DisplayOrderConfirmation;
use izi\prestashop\Hook\Front\DisplayPaymentReturn;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;

final class GeneralConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'generalconfigurationtype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
            ->add('apiConfiguration', ApiConfigurationType::class, [
                'label' => false,
                'error_mapping' => [
                    '.' => 'clientCredentials.clientSecret',
                ],
            ])
            ->add('ordersConfiguration', OrdersConfigurationType::class, [
                'label' => false,
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateGeneralConfigurationCommand::class,
            'validation_groups' => new GroupSequence(['Default', 'API']),
        ]);
    }
}
