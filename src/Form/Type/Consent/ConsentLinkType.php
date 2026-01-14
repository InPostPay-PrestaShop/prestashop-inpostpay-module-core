<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Consent;

use izi\prestashop\Common\Basket\ConsentLink as ConsentLinkModel;
use izi\prestashop\Configuration\DTO\ConsentLink;
use izi\prestashop\Form\Type\CmsPageChoiceType;
use izi\prestashop\Uuid\Uuid;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConsentLinkType extends AbstractType
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
            ->add('id', TextType::class, [
                'label' => $this->translator->trans('Identifier', [], 'Modules.Inpostizi.Consent'),
                'help' => $this->translator->trans('Unique link identifier. Placeholders created by adding the prefix "{prefix}" to identifiers will be replaced with corresponding links in the description.', [
                    '{prefix}' => ConsentLinkModel::PLACEHOLDER_PREFIX,
                ], 'Modules.Inpostizi.Consent'),
                'attr' => [
                    'maxlength' => Uuid::CANONICAL_FORMAT_LENGTH,
                ],
            ])
            ->add('cmsPageId', CmsPageChoiceType::class, [
                'input' => 'id',
                'label' => $this->translator->trans('Details page', [], 'Modules.Inpostizi.Consent'),
                'help' => $this->translator->trans('Specifies the page your customer will be taken to when they click the link in the mobile app.', [], 'Modules.Inpostizi.Consent'),
            ])
            ->add('labels', TranslatableType::class, [
                'required' => false,
                'type' => TextType::class,
                'label' => $this->translator->trans('Link text', [], 'Modules.Inpostizi.Consent'),
                'help' => $this->translator->trans('If left empty, "{default_text}" will be displayed.', [
                    '{default_text}' => 'link',
                ], 'Modules.Inpostizi.Consent'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConsentLink::class,
        ]);
    }
}
