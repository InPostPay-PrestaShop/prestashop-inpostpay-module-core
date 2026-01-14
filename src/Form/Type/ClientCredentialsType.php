<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Form\DataMapper\ClientCredentialsDataMapper;
use izi\prestashop\OAuth2\Authentication\ClientCredentials;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ClientCredentialsType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $helpText = $this->translator->trans('Please note that configuration varies depending on the selected environment. To obtain a value for the sandbox environment, please contact us via the contact form. To obtain a production value, log in to InPost and provide your store details.', [], 'Modules.Inpostizi.Environment');
        $builder
            ->add('clientId', TextType::class, [
                'label' => $this->translator->trans('Client ID', [], 'Modules.Inpostizi.Environment'),
                'help' => $helpText,
            ])
            ->add('clientSecret', MaskedPasswordType::class, [
                'label' => $this->translator->trans('Client secret', [], 'Modules.Inpostizi.Environment'),
                'help' => $helpText,
                'always_empty' => false,
            ])
            ->setDataMapper(new ClientCredentialsDataMapper());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClientCredentials::class,
            'empty_data' => null,
        ]);
    }
}
