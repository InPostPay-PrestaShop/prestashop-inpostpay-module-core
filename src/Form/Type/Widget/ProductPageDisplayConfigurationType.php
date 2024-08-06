<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\DTO\ProductPageDisplayConfiguration;
use izi\prestashop\Configuration\DTO\WidgetDisplayConfiguration;
use izi\prestashop\Form\Type\Product\ProductRestrictionsType;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductPageDisplayConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'productpagedisplayconfigurationtype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['binding_place'] = BindingPlace::ProductCard();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayConfiguration', WidgetDisplayConfigurationType::class, [
                'label' => $options['label'],
                'description' => $options['description'],
                'binding_place' => BindingPlace::ProductCard(),
            ])
            ->add('productRestrictions', ProductRestrictionsType::class, [
                'label' => $this->translator->l('Product restrictions', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Widget will not be rendered on the product page if the product matches any of the below conditions.', self::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => ProductPageDisplayConfiguration::class,
                'empty_data' => static function () {
                    $displayConfiguration = WidgetDisplayConfiguration::for(BindingPlace::ProductCard());

                    return new ProductPageDisplayConfiguration($displayConfiguration);
                },
                'description' => '',
            ])
            ->setAllowedTypes('description', 'string');
    }
}
