<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\QueryBuilder;
use izi\prestashop\Product\ProductAttribute;

/**
 * @template T of (\ProductAttribute|\Attribute)
 *
 * @extends ObjectRepository<\Combination>
 */
class CombinationRepository extends ObjectRepository
{
    /**
     * @var class-string<T>
     */
    private $attributeModelClass;

    public function __construct(ObjectManagerInterface $manager)
    {
        parent::__construct(\Combination::class, $manager);
        $this->attributeModelClass = class_exists(\ProductAttribute::class) ? \ProductAttribute::class : \Attribute::class;
    }

    /**
     * @return class-string<T>
     *
     * @internal
     */
    public function getAttributeModelClass(): string
    {
        return $this->attributeModelClass;
    }

    /**
     * @return array<int, non-empty-array<ProductAttribute<T>>> attributes by attribute group ID
     */
    public function getAvailableAttributesByProductId(int $productId, int $languageId): array
    {
        $data = $this
            ->createAttributesWithGroupQueryBuilder($languageId)
            ->innerJoin('product_attribute', 'pa', 'pa.id_product_attribute = pac.id_product_attribute')
            ->where('pa.id_product = ' . $productId)
            ->setOrderBy('pa.id_product_attribute, ag.position, a.position')
            ->build()
            ->getArrayResult();

        return $this->hydrateProductAttributes($data, $languageId);
    }

    /**
     * @return ProductAttribute<T>[]
     */
    public function getAttributesByCombinationId(int $combinationId, int $languageId): array
    {
        $data = $this
            ->createAttributesWithGroupQueryBuilder($languageId)
            ->where('pac.id_product_attribute = ' . $combinationId)
            ->build()
            ->getArrayResult();

        $attributes = $this->hydrateProductAttributes($data, $languageId);

        return array_map(static function (array $attributes): ProductAttribute {
            return $attributes[0];
        }, $attributes);
    }

    /**
     * @return array<int, T> attributes by group ID
     */
    public function getSimpleAttributesByCombinationId(int $combinationId, ?int $languageId = null): array
    {
        /** @var T[] $attributes */
        $attributes = $this
            ->createAttributesQueryBuilder($languageId)
            ->where('pac.id_product_attribute = ' . $combinationId)
            ->build()
            ->getResult();

        $result = [];

        foreach ($attributes as $attribute) {
            $result[$attribute->id_attribute_group] = $attribute;
        }

        return $result;
    }

    public function findByProductAndAttributeIds(int $productId, int ...$attributeIds): ?\Combination
    {
        if ([] === $attributeIds) {
            return null;
        }

        if (method_exists(\Product::class, 'getIdProductAttributeByIdAttributes')) {
            try {
                $combinationId = (int) \Product::getIdProductAttributeByIdAttributes($productId, $attributeIds);
            } catch (\PrestaShopObjectNotFoundException $e) {
                return null;
            }
        } else {
            $combinationId = (int) \Product::getIdProductAttributesByIdAttributes($productId, $attributeIds);
        }

        if (0 >= $combinationId) {
            return null;
        }

        return $this->find($combinationId);
    }

    public function getAttributeGroupIds(int $productId, int $combinationId): array
    {
        $attributes = \Product::getAttributesParams($productId, $combinationId);

        return array_column($attributes, 'id_attribute_group');
    }

    private function createAttributesWithGroupQueryBuilder(int $languageId): QueryBuilder
    {
        return $this
            ->createAttributesQueryBuilder($languageId)
            ->select('ag.is_color_group, ag.group_type, agl.public_name')
            ->select('agl.name AS group_name, ag.position as group_position')
            ->innerJoin('attribute_group', 'ag', 'ag.id_attribute_group = a.id_attribute_group')
            ->leftJoin('attribute_group_lang', 'agl', 'agl.id_attribute_group = ag.id_attribute_group AND agl.id_lang = ' . $languageId)
            ->groupBy('a.id_attribute')
            ->orderBy('ag.position, a.position');
    }

    /**
     * @return QueryBuilder<T>
     */
    private function createAttributesQueryBuilder(?int $languageId = null): QueryBuilder
    {
        return $this->manager
            ->getRepository($this->attributeModelClass)
            ->createQueryBuilder('a', $languageId)
            ->innerJoin('product_attribute_combination', 'pac', 'pac.id_attribute = a.id_attribute');
    }

    /**
     * @return array<int, non-empty-array<ProductAttribute<T>>> attributes by attribute group ID
     */
    private function hydrateProductAttributes(array $data, int $languageId): array
    {
        if ([] === $data) {
            return [];
        }

        $groups = $result = [];

        foreach ($data as $row) {
            /** @var T $attribute */
            $attribute = $this->manager->getHydrator()->hydrate($row, $this->attributeModelClass, null, $languageId);
            $groupId = (int) $attribute->id_attribute_group;

            if (!array_key_exists($groupId, $groups)) {
                $result[$groupId] = [];
                $groups[$groupId] = $this->manager->getHydrator()->hydrate(array_merge($row, [
                    'name' => $row['group_name'],
                    'position' => $row['group_position'],
                ]), \AttributeGroup::class, null, $languageId);
            }

            $result[$groupId][] = new ProductAttribute($attribute, $groups[$groupId]);
        }

        return $result;
    }
}
