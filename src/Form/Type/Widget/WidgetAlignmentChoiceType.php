<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\View\Widget\Alignment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => Alignment::cases(),
            'choice_value' => static function (?Alignment $alignment): string {
                if (null === $alignment) {
                    return '';
                }

                return $alignment->value;
            },
            'choice_label' => function (Alignment $alignment): string {
                return $this->getChoiceLabel($alignment);
            },
        ]);
    }

    private function getChoiceLabel(Alignment $alignment): string
    {
        switch ($alignment) {
            case Alignment::Left():
                return $this->translator->l('Left', self::TRANSLATION_SOURCE);
            case Alignment::Center():
                return $this->translator->l('Center', self::TRANSLATION_SOURCE);
            case Alignment::Right():
                return $this->translator->l('Right', self::TRANSLATION_SOURCE);
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}
