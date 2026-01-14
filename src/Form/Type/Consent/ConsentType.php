<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Consent;

use izi\prestashop\Common\Basket\ConsentLink;
use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Form\EventListener\ReindexDataListener;
use izi\prestashop\Form\Type\EnumType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConsentType extends AbstractType
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
        $view['additionalLinks']->vars['add_entry_label'] = $this->translator->trans('Add another link', [], 'Modules.Inpostizi.Consent');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('descriptions', TranslatableType::class, [
                'label' => $this->translator->trans('Consent text in the mobile app', [], 'Modules.Inpostizi.Consent'),
                'type' => TextType::class,
                'help' => nl2br(\sprintf(
                    "%s %s\n %s",
                    $this->translator->trans('Add a description to be displayed with the consent in the InPost mobile app.', [], 'Modules.Inpostizi.Consent'),
                    $this->translator->trans('If blank in a language other than the shop\'s default, the value for the default language will be used.', [], 'Modules.Inpostizi.Consent'),
                    $this->translator->trans('Use link identifiers prefixed with "{prefix}" to control the position of links.', [
                        '{prefix}' => ConsentLink::PLACEHOLDER_PREFIX,
                    ], 'Modules.Inpostizi.Consent')
                )),
            ])
            ->add('requirementType', EnumType::class, [
                'class' => ConsentRequirementType::class,
                'label' => $this->translator->trans('Requiredness', [], 'Modules.Inpostizi.Consent'),
                'help' => $this->translator->trans('Specify whether the consent is required or optional.', [], 'Modules.Inpostizi.Consent'),
            ])
            ->add('link', ConsentLinkType::class, [
                'label' => false,
            ])
            ->add('additionalLinks', CollectionType::class, [
                'entry_type' => ConsentLinkType::class,
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => [
                    'label' => false,
                ],
                'error_bubbling' => false,
                'label' => $this->translator->trans('Links', [], 'Modules.Inpostizi.Consent'),
                'attr' => [
                    'data-max-count' => Consent::ADDITIONAL_LINKS_COUNT_MAX,
                ],
            ])
            ->get('additionalLinks')
            ->addEventSubscriber(new ReindexDataListener());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consent::class,
            'validation_groups' => new GroupSequence(['Default', Consent::VALIDATION_GROUP]),
        ]);
    }
}
