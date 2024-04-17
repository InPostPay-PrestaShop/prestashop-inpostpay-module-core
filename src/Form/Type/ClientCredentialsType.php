<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Form\DataMapper\ClientCredentialsDataMapper;
use izi\prestashop\OAuth2\Authentication\ClientCredentials;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ClientCredentialsType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'clientcredentialstype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clientId', TextType::class, [
                'label' => $this->translator->l('Client ID', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Please note that the client ID varies depending on the selected environment. To get a sandbox Client ID contact us through the contact form. To get a production Client ID log in to InPost and complete your store details.', self::TRANSLATION_SOURCE),
            ])
            ->add('clientSecret', MaskedPasswordType::class, [
                'label' => $this->translator->l('Client secret', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Note that Client Secret varies depending on the environment you choose. To get sandboxed Client Secret contact us through the contact form. To get production Client Secret log in to InPost and complete your store details.', self::TRANSLATION_SOURCE),
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
