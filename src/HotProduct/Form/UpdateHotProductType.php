<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\Form;

use izi\prestashop\Form\DataTransformer\DateTimeImmutableToDateTimeTransformer as DateTransformerPolyfill;
use izi\prestashop\HotProduct\Message\UpdateHotProductCommand;
use PrestaShopBundle\Form\Admin\Type\DatePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\DataTransformerChain;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeImmutableToDateTimeTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UpdateHotProductType extends AbstractType
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
            ->add('availableFrom', DatePickerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Available from', [], 'Modules.Inpostizi.Hotproduct'),
                'date_format' => 'YYYY-MM-DD HH:mm:ss',
            ])
            ->add('availableTo', DatePickerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Available to', [], 'Modules.Inpostizi.Hotproduct'),
                'date_format' => 'YYYY-MM-DD HH:mm:ss',
            ]);

        $dateTransformer = new DataTransformerChain([
            class_exists(DateTimeImmutableToDateTimeTransformer::class) ? new DateTimeImmutableToDateTimeTransformer() : new DateTransformerPolyfill(),
            new DateTimeToStringTransformer(),
        ]);

        foreach (['availableFrom', 'availableTo'] as $name) {
            $builder->get($name)->addModelTransformer($dateTransformer);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateHotProductCommand::class,
        ]);
    }
}
