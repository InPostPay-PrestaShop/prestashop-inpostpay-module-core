<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Command\Config\UpdateConsentsConfigurationCommand;
use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Form\EventListener\ReindexDataListener;
use izi\prestashop\Form\Type\Consent\ConsentType;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;

final class ConsentsConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'consentsconfigurationtype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view['consents']->vars['add_entry_label'] = $this->translator->l('Add another consent', self::TRANSLATION_SOURCE);
        $view['consents']->vars['max_count_message'] = sprintf($this->translator->l('The maximum number of consents is %d.', self::TRANSLATION_SOURCE), UpdateConsentsConfigurationCommand::CONSENTS_COUNT_MAX);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('consents', CollectionType::class, [
                'entry_type' => ConsentType::class,
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => [
                    'label' => false,
                ],
                'label' => false,
                'attr' => [
                    'data-max-count' => UpdateConsentsConfigurationCommand::CONSENTS_COUNT_MAX,
                ],
            ])
            ->get('consents')
            ->addEventSubscriber(new ReindexDataListener());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateConsentsConfigurationCommand::class,
            'validation_groups' => new GroupSequence(['Default', Consent::VALIDATION_GROUP]),
            'label' => false,
        ]);
    }
}
