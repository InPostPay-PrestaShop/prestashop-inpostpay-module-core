<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CartRuleOptionsType extends AbstractType
{
    /**
     * @internal
     */
    public const TRANSLATION_SOURCE = 'cartruleoptionstype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(LegacyTranslator $translator, ?\Context $context = null)
    {
        $this->translator = $translator;
        $this->context = $context ?? \Context::getContext();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('omnibus', ChoiceType::class, [
                'label' => $this->translator->l('Falls under the Omnibus Directive', self::TRANSLATION_SOURCE),
                'expanded' => true,
                'choices' => [
                    $this->translator->l('Yes', self::TRANSLATION_SOURCE) => true,
                    $this->translator->l('No', self::TRANSLATION_SOURCE) => false,
                ],
            ])
            ->add('promoDetailsPageId', ObjectModelType::class, [
                'required' => false,
                'class' => \CMS::class,
                'input' => 'id',
                'choice_label' => static function (\CMS $page): string {
                    return (string) $page->meta_title;
                },
                'label' => $this->translator->l('Promotion details page', self::TRANSLATION_SOURCE),
                'placeholder' => $this->translator->l('Use default page', self::TRANSLATION_SOURCE),
                'help' => implode("\n", [
                    sprintf($this->translator->l('In order for the available promotion data to be passed to the mobile app, the "%s" option must be enabled for the cart rule.', self::TRANSLATION_SOURCE), $this->context->getTranslator()->trans('Highlight', [], 'Admin.Catalog.Feature')),
                    $this->translator->l('If neither the default value nor the cart rule specific value is configured, the available promotion data will not be passed to the mobile app.', self::TRANSLATION_SOURCE),
                    $this->translator->l('The default page can be selected on the module configuration page.', self::TRANSLATION_SOURCE),
                ]),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->l('InPost Pay options', self::TRANSLATION_SOURCE),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'inpostizi_cart_rule_options';
    }
}
