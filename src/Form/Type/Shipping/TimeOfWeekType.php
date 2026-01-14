<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Configuration\DTO\Shipping\TimeOfWeek;
use izi\prestashop\Configuration\DTO\Shipping\WeekDay;
use izi\prestashop\Form\Type\EnumType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TimeOfWeekType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('weekDay', EnumType::class, [
                'class' => WeekDay::class,
                'label' => false,
            ])
            ->add('time', TimeType::class, [
                'label' => false,
                'input' => 'datetime_immutable',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimeOfWeek::class,
        ]);
    }
}
