<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\ApiConfiguration;
use izi\prestashop\Environment\EnvironmentType;
use izi\prestashop\Environment\UatEnvironment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApiConfigurationType extends AbstractType
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
            ->add('environmentType', EnumType::class, [
                'label' => $this->translator->trans('Environment', [], 'Modules.Inpostizi.Environment'),
                'help' => $this->translator->trans('Select the environment in which you want the InPost Pay service to run. Remember to ensure the service is working properly in your store before switching to the production environment.', [], 'Modules.Inpostizi.Environment'),
                'class' => EnvironmentType::class,
                'choices' => array_filter(EnvironmentType::cases(), static function (EnvironmentType $env) {
                    return EnvironmentType::Uat() !== $env || class_exists(UatEnvironment::class);
                }),
            ])
            ->add('merchantClientId', TextType::class, [
                'label' => $this->translator->trans('Merchant client ID', [], 'Modules.Inpostizi.Environment'),
                'help' => $this->translator->trans('Merchant client ID is a value assigned by InPost. In order to obtain the value for the sandbox environment, send a request to {email}. The value for the production environment can be obtained in the merchant panel.', [
                    '{email}' => 'integracjapay@inpost.pl',
                ], 'Modules.Inpostizi.Environment'),
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
