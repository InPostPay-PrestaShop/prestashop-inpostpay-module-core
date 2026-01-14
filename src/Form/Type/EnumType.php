<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Enum\Enum;
use izi\prestashop\Form\DataTransformer\EnumDataTransformer;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EnumType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ('enum' === $options['input']) {
            return;
        }

        $builder->addModelTransformer(new ReversedTransformer(new EnumDataTransformer($options['class'])));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('class')
            ->setDefaults([
                'choices' => function (Options $options): array {
                    /** @var class-string<Enum> $class */
                    $class = $options['class'];

                    return $class::cases();
                },
                'choice_label' => function (Enum $choice): string {
                    return $choice instanceof TranslatableInterface
                        ? $choice->trans($this->translator)
                        : $choice->name;
                },
                'choice_value' => static function (?Enum $choice): ?string {
                    if (null === $choice) {
                        return null;
                    }

                    return (string) $choice->value;
                },
                'input' => 'enum',
            ])
            ->setAllowedTypes('class', 'string')
            ->setAllowedValues('class', static function (string $class): bool {
                return is_a($class, Enum::class, true);
            })
            ->setAllowedValues('input', ['enum', 'value']);
    }
}
