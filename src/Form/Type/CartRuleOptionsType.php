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
    private const TRANSLATION_SOURCE = 'cartruleoptionstype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('omnibus', ChoiceType::class, [
            'label' => $this->translator->l('Falls under the Omnibus Directive', self::TRANSLATION_SOURCE),
            'expanded' => true,
            'choices' => [
                $this->translator->l('Yes', self::TRANSLATION_SOURCE) => true,
                $this->translator->l('No', self::TRANSLATION_SOURCE) => false,
            ],
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
