<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Environment\EnvironmentType;
use izi\prestashop\Environment\UatEnvironment;
use izi\prestashop\Form\DataTransformer\EnumDataTransformer;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EnvironmentChoiceType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'environmentchoicetype';

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
        $builder->addModelTransformer(new EnumDataTransformer(EnvironmentType::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->getChoices(),
        ]);
    }

    private function getChoices(): array
    {
        $choices = [
            $this->translator->l('Sandbox', self::TRANSLATION_SOURCE) => EnvironmentType::Sandbox()->value,
            $this->translator->l('Production', self::TRANSLATION_SOURCE) => EnvironmentType::Production()->value,
        ];

        if (!class_exists(UatEnvironment::class)) {
            return $choices;
        }

        return array_merge([
            $this->translator->l('UAT', self::TRANSLATION_SOURCE) => EnvironmentType::Uat()->value,
        ], $choices);
    }
}
