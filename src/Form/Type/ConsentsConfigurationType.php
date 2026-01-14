<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Command\Config\UpdateConsentsConfigurationCommand;
use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Form\EventListener\ReindexDataListener;
use izi\prestashop\Form\Type\Consent\ConsentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConsentsConfigurationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view['consents']->vars['add_entry_label'] = $this->translator->trans('Add another consent', [], 'Modules.Inpostizi.Consent');
        $view['consents']->vars['max_count_message'] = $this->translator->trans('The maximum number of consents is {max}.', [
            '{max}' => UpdateConsentsConfigurationCommand::CONSENTS_COUNT_MAX,
        ], 'Modules.Inpostizi.Consent');
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
