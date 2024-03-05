<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Configuration\DTO\Shipping\TimeOfWeekRange;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TimeOfWeekRangeType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'timeofweekrangetype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start', TimeOfWeekType::class, [
                'label' => $this->translator->l('Available from', self::TRANSLATION_SOURCE),
            ])
            ->add('end', TimeOfWeekType::class, [
                'label' => $this->translator->l('Available to', self::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimeOfWeekRange::class,
        ]);
    }
}
