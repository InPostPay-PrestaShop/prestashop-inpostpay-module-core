<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\View\Widget\Size;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WidgetSizeChoiceType extends AbstractType
{
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
        $builder->addModelTransformer(new CallbackTransformer(static function ($value) {
            return $value ?? Size::Large();
        }, static function ($value) {
            return $value;
        }));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => Size::cases(),
            'choice_value' => static function (?Size $size): string {
                if (null === $size) {
                    return '';
                }

                return $size->value;
            },
            'choice_label' => function (Size $size): string {
                return $size->trans($this->translator);
            },
        ]);
    }
}
