<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Configuration\DTO\HtmlStyles;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HtmlStylesType extends AbstractType
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
            ->add('marginTop', IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Margin top', [], 'Modules.Inpostizi.Gui'),
                'unit' => 'px',
            ])
            ->add('marginLeft', IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Margin left', [], 'Modules.Inpostizi.Gui'),
                'unit' => 'px',
            ])
            ->add('marginRight', IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Margin right', [], 'Modules.Inpostizi.Gui'),
                'unit' => 'px',
            ])
            ->add('marginBottom', IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Margin bottom', [], 'Modules.Inpostizi.Gui'),
                'unit' => 'px',
            ])
            ->add('justifyContent', ChoiceType::class, [
                'choices' => [
                    $this->translator->trans('Left', [], 'Modules.Inpostizi.Gui') => 'start',
                    $this->translator->trans('Center', [], 'Modules.Inpostizi.Gui') => 'center',
                    $this->translator->trans('Right', [], 'Modules.Inpostizi.Gui') => 'end',
                ],
                'label' => $this->translator->trans('Alignment', [], 'Modules.Inpostizi.Gui'),
                'help' => $this->translator->trans('Specifies the orientation of the widget in the space available for it. If your template allocates a narrow space for the widget the setting will not affect the appearance.', [], 'Modules.Inpostizi.Gui'),
                'attr' => [
                    'class' => 'js-widget-attribute-provider',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HtmlStyles::class,
        ]);
    }
}
