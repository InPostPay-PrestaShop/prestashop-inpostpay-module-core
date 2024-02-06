<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;

final class GeneralConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'generalconfigurationtype';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enableForEveryone', ChoiceType::class, [
                'choices' => [
                    $this->module->l('to everyone', self::TRANSLATION_SOURCE) => true,
                    $this->module->l('to testers', self::TRANSLATION_SOURCE) => false,
                ],
                'property_path' => 'generalConfiguration.enabledForEveryone',
                'label' => $this->module->l('Display widget', self::TRANSLATION_SOURCE),
                'help' => $this->module->l('If you select "to testers" the widget will be visible only to those who are supposed to see it. To display the widget in this mode in a web browser, type the address of your store with \'?showIzi=true\' Example: https://mojsklep.pl?showIzi=true', self::TRANSLATION_SOURCE),
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
                'label' => $this->module->l('Maximum number of suggested products', self::TRANSLATION_SOURCE),
                'help' => $this->module->l('To show suggested products, complete the Accessories Products section in the product configuration.', self::TRANSLATION_SOURCE),
                'attr' => [
                    'placeholder' => $this->module->l('unlimited', self::TRANSLATION_SOURCE),
                    'min' => 0,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UpdateGeneralConfigurationCommand::class,
            'validation_groups' => new GroupSequence(['Default', 'API']),
        ]);
    }
}
