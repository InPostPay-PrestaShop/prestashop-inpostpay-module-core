<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Consent;

use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Form\DataTransformer\EnumDataTransformer;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ConsentRequirementChoiceType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'consentrequirementchoicetype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new EnumDataTransformer(ConsentRequirementType::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                $this->translator->l('Optional', self::TRANSLATION_SOURCE) => ConsentRequirementType::Optional()->value,
                $this->translator->l('Always required', self::TRANSLATION_SOURCE) => ConsentRequirementType::RequiredAlways()->value,
                $this->translator->l('Required once', self::TRANSLATION_SOURCE) => ConsentRequirementType::RequiredOnce()->value,
            ],
        ]);
    }
}
