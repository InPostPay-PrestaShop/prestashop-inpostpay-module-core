<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Form\DataTransformer\EnumDataTransformer;
use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\View\Widget\Alignment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WidgetAlignmentChoiceType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'widgetalignmentchoicetype';

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
        $builder->addModelTransformer(new EnumDataTransformer(Alignment::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                $this->translator->l('Left', self::TRANSLATION_SOURCE) => Alignment::Left()->value,
                $this->translator->l('Center', self::TRANSLATION_SOURCE) => Alignment::Center()->value,
                $this->translator->l('Right', self::TRANSLATION_SOURCE) => Alignment::Right()->value,
            ],
        ]);
    }
}
