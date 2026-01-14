<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Product;

use izi\prestashop\Configuration\DTO\Product\ProductRestrictions;
use izi\prestashop\Form\Type\EnumType;
use izi\prestashop\Form\Type\ObjectModelType;
use izi\prestashop\Product\ProductType;
use izi\prestashop\Product\Restriction\RestrictedAction;
use PrestaShopBundle\Form\Admin\Type\CategoryChoiceTreeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductRestrictionsType extends AbstractType
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
            ->add('restrictedAction', EnumType::class, [
                'class' => RestrictedAction::class,
                'label' => $this->translator->trans('Restricted action', [], 'Modules.Inpostizi.Product'),
                'help' => \sprintf(
                    $this->translator->trans('If either the "{hide_widget}" or "{disallow_order}" option is selected, the widget will not be displayed on the product page.', [
                        '{hide_widget}' => RestrictedAction::HideWidget()->trans($this->translator),
                        '{disallow_order}' => RestrictedAction::DisallowOrder()->trans($this->translator),
                    ], 'Modules.Inpostizi.Product')
                ),
            ])
            ->add('productTypes', EnumType::class, [
                'class' => ProductType::class,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => $this->translator->trans('Product type', [], 'Modules.Inpostizi.Product'),
            ])
            ->add('categoryIds', CategoryChoiceTreeType::class, [
                'multiple' => true,
                'required' => false,
                'label' => $this->translator->trans('Default category', [], 'Admin.Catalog.Feature'),
                'empty_data' => [],
            ])
            ->add('manufacturerIds', ObjectModelType::class, [
                'class' => \Manufacturer::class,
                'input' => 'id',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => $this->translator->trans('Brand', [], 'Admin.Global'),
            ])
            ->add('attributeGroupIds', ObjectModelType::class, [
                'class' => \AttributeGroup::class,
                'input' => 'id',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => $this->translator->trans('Attribute group', [], 'Admin.Catalog.Feature'),
                'help' => $this->translator->trans('The restriction will be applied if the product combination has an attribute from the selected groups.', [], 'Modules.Inpostizi.Product'),
            ])
            ->add('featureIds', ObjectModelType::class, [
                'class' => \Feature::class,
                'input' => 'id',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => $this->translator->trans('Feature', [], 'Admin.Catalog.Feature'),
                'help' => $this->translator->trans('The restriction will be applied if the product has any of the selected features.', [], 'Modules.Inpostizi.Product'),
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
                $form = $event->getForm();

                $categoryIdsConfig = $form->get('categoryIds')->getConfig();
                $type = \get_class($categoryIdsConfig->getType()->getInnerType());

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
