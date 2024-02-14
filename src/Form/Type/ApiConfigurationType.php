<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\ApiConfiguration;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ApiConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'apiconfigurationtype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('environmentType', EnvironmentChoiceType::class, [
                'label' => $this->translator->l('Environment', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Select the environment on which you want to show the InPost Pay service. Remember to make sure that the service in your store is working properly before switching to the production environment', self::TRANSLATION_SOURCE),
            ])
            ->add('clientCredentials', ClientCredentialsType::class, [
                'required' => false,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApiConfiguration::class,
        ]);
    }
}
