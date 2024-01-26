<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Command\Config\UpdateConsentsConfigurationCommand;
use izi\prestashop\Form\Type\Consent\ConsentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ConsentsConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'consentsconfigurationtype';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view['consents']->vars['add_consent_label'] = $this->module->l('Add another consent', self::TRANSLATION_SOURCE);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('consents', CollectionType::class, [
                'entry_type' => ConsentType::class,
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateConsentsConfigurationCommand::class,
        ]);
    }
}
