<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Form\DataTransformer\EnumDataTransformer;
use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\View\Widget\FrameStyle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WidgetFrameStyleChoiceType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'widgetframestylechoicetype';

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
        $builder->addModelTransformer(new EnumDataTransformer(FrameStyle::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                $this->translator->l('Rounded', self::TRANSLATION_SOURCE) => FrameStyle::Rounded()->value,
                $this->translator->l('Round', self::TRANSLATION_SOURCE) => FrameStyle::Round()->value,
            ],
            'required' => false,
            'placeholder' => $this->translator->l('Rectangular', self::TRANSLATION_SOURCE),
        ]);
    }
}
