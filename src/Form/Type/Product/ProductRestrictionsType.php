<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Product;

use izi\prestashop\Configuration\DTO\Product\ProductRestrictions;
use izi\prestashop\Form\Type\Compatibility\CategoryChoiceTreeType as CategoryChoiceTreeTypePolyfill;
use izi\prestashop\Form\Type\ObjectModelType;
use izi\prestashop\Form\Type\SwitchType as SwitchTypePolyfill;
use izi\prestashop\Product\ProductType;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShopBundle\Form\Admin\Type\CategoryChoiceTreeType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

final class ProductRestrictionsType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'productrestrictionstype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(LegacyTranslator $translator, \Context $context)
    {
        $this->translator = $translator;
        $this->context = $context;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categoryTreeType = class_exists(CategoryChoiceTreeType::class)
            ? CategoryChoiceTreeType::class
            : CategoryChoiceTreeTypePolyfill::class;

        $switchType = class_exists(SwitchType::class)
            ? SwitchType::class
            : SwitchTypePolyfill::class;

        $builder
            ->add('blockOrder', $switchType, [
                'empty_data' => false,
                'label' => $this->translator->l('Disallow ordering restricted products', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('If enabled, placing an order via the mobile app will not be possible if the cart contains  a product that meets any of the conditions below.', self::TRANSLATION_SOURCE),
            ])
            ->add('productTypes', ChoiceType::class, [
                'choices' => ProductType::cases(),
                'choice_value' => function (ProductType $productType): string {
                    return $productType->value;
                },
                'choice_label' => function (ProductType $productType): string {
                    return $productType->trans($this->translator);
                },
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => $this->translator->l('Product type', self::TRANSLATION_SOURCE),
            ])
            ->add('categoryIds', $categoryTreeType, [
                'multiple' => true,
                'required' => false,
                'label' => $this->context->getTranslator()->trans('Default category', [], 'Admin.Catalog.Feature'),
                'empty_data' => [],
            ])
            ->add('manufacturerIds', ObjectModelType::class, [
                'class' => \Manufacturer::class,
                'input' => 'id',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => $this->context->getTranslator()->trans('Brand', [], 'Admin.Global'),
            ])
            ->add('attributeGroupIds', ObjectModelType::class, [
                'class' => \AttributeGroup::class,
                'input' => 'id',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => $this->context->getTranslator()->trans('Attribute group', [], 'Admin.Catalog.Feature'),
                'help' => $this->translator->l('Widget will not be render if the product combination has an attribute from the selected groups.', self::TRANSLATION_SOURCE),
            ])
            ->add('featureIds', ObjectModelType::class, [
                'class' => \Feature::class,
                'input' => 'id',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => $this->context->getTranslator()->trans('Feature', [], 'Admin.Catalog.Feature'),
                'help' => $this->translator->l('Widget will not be render if the product has any of the selected features.', self::TRANSLATION_SOURCE),
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
                $form = $event->getForm();

                $categoryIdsConfig = $form->get('categoryIds')->getConfig();
                $type = get_class($categoryIdsConfig->getType()->getInnerType());

                $options = $categoryIdsConfig->getOptions();
                $options['constraints'][] = new Choice([
                    'choices' => $this->flattenCategoryIdChoices($options['choices_tree'], $options['choice_value'], $options['choice_children']),
                    'multiple' => $options['multiple'],
                    'strict' => true,
                ]);

                $form->add('categoryIds', $type, $options);
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductRestrictions::class,
        ]);
    }

    private function flattenCategoryIdChoices(array $choicesTree, string $choiceValueName, string $choiceChildrenName): array
    {
        $choices = [];

        foreach ($choicesTree as $choice) {
            $choices[] = [(string) $choice[$choiceValueName]];
            if ([] === ($choice[$choiceChildrenName] ?? [])) {
                continue;
            }

            $choices[] = $this->flattenCategoryIdChoices($choice[$choiceChildrenName], $choiceValueName, $choiceChildrenName);
        }

        return array_merge(...$choices);
    }
}
