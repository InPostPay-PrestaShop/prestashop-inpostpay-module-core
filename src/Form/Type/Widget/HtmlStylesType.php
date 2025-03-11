<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Configuration\DTO\HtmlStyles;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class HtmlStylesType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'htmlstylestype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('marginTop', IntegerType::class, [
                'required' => false,
                'label' => $this->translator->l('Margin top', self::TRANSLATION_SOURCE),
                'unit' => 'px',
            ])
            ->add('marginLeft', IntegerType::class, [
                'required' => false,
                'label' => $this->translator->l('Margin left', self::TRANSLATION_SOURCE),
                'unit' => 'px',
            ])
            ->add('marginRight', IntegerType::class, [
                'required' => false,
                'label' => $this->translator->l('Margin right', self::TRANSLATION_SOURCE),
                'unit' => 'px',
            ])
            ->add('marginBottom', IntegerType::class, [
                'required' => false,
                'label' => $this->translator->l('Margin bottom', self::TRANSLATION_SOURCE),
                'unit' => 'px',
            ])
            ->add('justifyContent', ChoiceType::class, [
                'choices' => [
                    $this->translator->l('Left', self::TRANSLATION_SOURCE) => 'start',
                    $this->translator->l('Center', self::TRANSLATION_SOURCE) => 'center',
                    $this->translator->l('Right', self::TRANSLATION_SOURCE) => 'end',
                ],
                'label' => $this->translator->l('Alignment', 'widgetconfigurationtype'),
                'help' => $this->translator->l('Specifies the orientation of the widget in the space available for it. If your template allocates a narrow space for the widget the setting will not affect the appearance.', 'widgetconfigurationtype'),
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
