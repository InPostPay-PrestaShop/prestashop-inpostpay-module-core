<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Configuration\DTO\Shipping\WeekDay;
use izi\prestashop\Form\DataTransformer\EnumDataTransformer;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WeekDayChoiceType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'weekdaychoicetype';

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
        $builder->addModelTransformer(new EnumDataTransformer(WeekDay::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                $this->translator->l('Monday', self::TRANSLATION_SOURCE) => WeekDay::Monday()->value,
                $this->translator->l('Tuesday', self::TRANSLATION_SOURCE) => WeekDay::Tuesday()->value,
                $this->translator->l('Wednesday', self::TRANSLATION_SOURCE) => WeekDay::Wednesday()->value,
                $this->translator->l('Thursday', self::TRANSLATION_SOURCE) => WeekDay::Thursday()->value,
                $this->translator->l('Friday', self::TRANSLATION_SOURCE) => WeekDay::Friday()->value,
                $this->translator->l('Saturday', self::TRANSLATION_SOURCE) => WeekDay::Saturday()->value,
                $this->translator->l('Sunday', self::TRANSLATION_SOURCE) => WeekDay::Sunday()->value,
            ],
        ]);
    }
}
