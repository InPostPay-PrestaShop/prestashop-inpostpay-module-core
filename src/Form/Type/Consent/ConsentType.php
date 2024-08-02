<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Consent;

use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Form\EventListener\ReindexDataListener;
use izi\prestashop\Form\Type\TranslatableType as TranslatableTypePolyfill;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;

final class ConsentType extends AbstractType
{
    /**
     * @internal
     */
    public const TRANSLATION_SOURCE = 'consenttype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view['additionalLinks']->vars['add_entry_label'] = $this->translator->l('Add another link', self::TRANSLATION_SOURCE);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translatableClass = class_exists(TranslatableType::class)
            ? TranslatableType::class
            : TranslatableTypePolyfill::class;

        $builder
            ->add('descriptions', $translatableClass, [
                'label' => $this->translator->l('Consent text in the mobile app', self::TRANSLATION_SOURCE),
                'type' => TextType::class,
                'help' => nl2br(sprintf(
                    "%s %s\n %s",
                    $this->translator->l('Add a description to be displayed with the consent in the InPost mobile app.', self::TRANSLATION_SOURCE),
                    $this->translator->l('If blank in a language other than the shop\'s default, the value for the default language will be used.', self::TRANSLATION_SOURCE),
                    $this->translator->l('Use link identifiers prefixed with "#" to control the position of links.', self::TRANSLATION_SOURCE)
                )),
            ])
            ->add('requirementType', ConsentRequirementChoiceType::class, [
                'label' => $this->translator->l('Requiredness', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Specify whether consent is required or optional.', self::TRANSLATION_SOURCE),
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
                'label' => $this->translator->l('Links', self::TRANSLATION_SOURCE),
                'attr' => [
                    'data-max-count' => Consent::ADDITIONAL_LINKS_COUNT_MAX,
                ],
            ])
            ->get('additionalLinks')
            ->addEventSubscriber(new ReindexDataListener());

        // usages moved to another file - kept for AdminTranslationsController message extraction purpose
        // ->l('Details page'),
        // ->l('Specifies the page to which your customer will be redirected for a target who clicks on a given consent in the InPost mobile app.'),
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consent::class,
            'validation_groups' => new GroupSequence(['Default', Consent::VALIDATION_GROUP]),
        ]);
    }
}
