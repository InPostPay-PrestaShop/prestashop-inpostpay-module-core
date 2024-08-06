<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Compatibility;

use PrestaShop\PrestaShop\Adapter\Category\CategoryDataProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * Polyfill for PS < 1.7.6.
 *
 * @see \PrestaShopBundle\Form\Admin\Type\CategoryChoiceTreeType
 * @see \PrestaShop\PrestaShop\Adapter\Form\ChoiceProvider\CategoryTreeChoiceProvider
 */
class CategoryChoiceTreeType extends AbstractType
{
    /**
     * @var CategoryDataProvider
     */
    private $dataProvider;

    public function __construct(CategoryDataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function getBlockPrefix(): string
    {
        return 'inpostizi_category_choice_tree';
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $selectedData = [];

        if (null !== $form->getData()) {
            $selectedData = is_array($form->getData()) ? $form->getData() : [$form->getData()];
        }

        $view->vars['multiple'] = $options['multiple'];
        $view->vars['choices_tree'] = $this->getFormattedChoicesTree($options, $selectedData);
        $view->vars['choice_label'] = $options['choice_label'];
        $view->vars['choice_value'] = $options['choice_value'];
        $view->vars['choice_children'] = $options['choice_children'];
        $view->vars['disabled_values'] = $options['disabled_values'];
        $view->vars['selected_values'] = $selectedData;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'choice_label' => 'name',
                'choice_value' => 'id_category',
                'choice_children' => 'children',
                'disabled_values' => [],
                'multiple' => false,
                'only_active' => false,
                'root_category_id' => null,
                'choices_tree' => null,
                'constraints' => [],
                'compound' => false,
            ])
            ->setAllowedTypes('choice_value', 'string')
            ->setAllowedTypes('choice_label', 'string')
            ->setAllowedTypes('choice_children', 'string')
            ->setAllowedTypes('disabled_values', 'array')
            ->setAllowedTypes('multiple', 'bool')
            ->setAllowedTypes('only_active', 'bool')
            ->setAllowedTypes('root_category_id', ['null', 'int'])
            ->setAllowedTypes('choices_tree', ['null', 'array'])
            ->setNormalizer('root_category_id', function (Options $options, ?int $value): int {
                return $value ?? $this->getRootCategoryId();
            })
            ->setNormalizer('choices_tree', function (Options $options, ?array $value): array {
                return $value ?? $this->getChoices($options['root_category_id'], $options['only_active']);
            });
    }

    private function getFormattedChoicesTree(array $options, array $selectedData): array
    {
        $tree = $options['choices_tree'];

        foreach ($tree as &$choice) {
            $this->fillChoiceWithChildrenSelection(
                $choice,
                $options['choice_value'],
                $options['choice_children'],
                $selectedData
            );
        }

        return $tree;
    }

    private function fillChoiceWithChildrenSelection(array &$choice, string $choiceValueName, string $choiceChildrenName, array $selectedValues): bool
    {
        $isSelected = false;
        $isChildrenSelected = false;

        if (in_array($choice[$choiceValueName], $selectedValues, true)) {
            $isSelected = true;
        }

        if (isset($choice[$choiceChildrenName])) {
            foreach ($choice[$choiceChildrenName] as &$child) {
                $selected = $this->fillChoiceWithChildrenSelection(
                    $child,
                    $choiceValueName,
                    $choiceChildrenName,
                    $selectedValues
                );

                if ($selected) {
                    $isChildrenSelected = true;
                }
            }
            unset($child);
        }

        $choice['has_selected_children'] = $isChildrenSelected;

        return $isSelected || $isChildrenSelected;
    }

    public function getChoices(int $rootCategoryId, bool $onlyActive): array
    {
        $categories = $this->dataProvider->getNestedCategories($rootCategoryId, false, $onlyActive);

        $choices = [];
        foreach ($categories as $category) {
            $choices[] = $this->buildChoiceTree($category);
        }

        return $choices;
    }

    private function buildChoiceTree(array $category): array
    {
        $tree = [
            'id_category' => $category['id_category'],
            'name' => $category['name'],
        ];

        if (isset($category['children'])) {
            foreach ($category['children'] as $childCategory) {
                $tree['children'][] = $this->buildChoiceTree($childCategory);
            }
        }

        return $tree;
    }

    private function getRootCategoryId(): int
    {
        if (is_callable([$this->dataProvider, 'getRootCategory'])) {
            return (int) $this->dataProvider->getRootCategory()->id;
        }

        return (int) \Category::getRootCategory()->id;
    }
}
