<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Configuration\DTO\Shipping\TimeOfWeekRange;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TimeOfWeekRangeType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start', TimeOfWeekType::class, [
                'label' => $this->translator->trans('Available from', [], 'Modules.Inpostizi.Shipping'),
                'help' => $this->translator->trans('inclusive', [], 'Modules.Inpostizi.Shipping'),
            ])
            ->add('end', TimeOfWeekType::class, [
                'label' => $this->translator->trans('Available to', [], 'Modules.Inpostizi.Shipping'),
                'help' => $this->translator->trans('exclusive', [], 'Modules.Inpostizi.Shipping'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimeOfWeekRange::class,
        ]);
    }
}
