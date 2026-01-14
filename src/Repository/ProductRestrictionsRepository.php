<?php

declare(strict_types=1);

namespace izi\prestashop\Repository;

use izi\prestashop\Configuration\DTO\Product\ProductRestrictions;
use izi\prestashop\Database\Connection;

final class ProductRestrictionsRepository implements ProductRestrictionsRepositoryInterface
{
    public const CATEGORY_RESTRICTIONS_TABLE = 'inpostizi_prod_category_bl';
    public const MANUFACTURER_RESTRICTIONS_TABLE = 'inpostizi_prod_manufacturer_bl';
    public const ATTRIBUTE_GROUP_RESTRICTIONS_TABLE = 'inpostizi_prod_attr_group_bl';
    public const FEATURE_RESTRICTIONS_TABLE = 'inpostizi_prod_feature_bl';

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function isCategoryRestricted(int $categoryId, ?int $shopId = null): bool
    {
        if (0 >= $categoryId) {
            return false;
        }

        $subQuery = $this
            ->createByShopIdQueryBuilder(self::CATEGORY_RESTRICTIONS_TABLE, $shopId)
            ->where('id_category = ' . $categoryId);

        return $this->exists($subQuery);
    }

    public function hasCategoryRestrictions(?int $shopId = null): bool
    {
        $subQuery = $this->createByShopIdQueryBuilder(self::CATEGORY_RESTRICTIONS_TABLE, $shopId);

        return $this->exists($subQuery);
    }

    public function isManufacturerRestricted(int $manufacturerId, ?int $shopId = null): bool
    {
        if (0 >= $manufacturerId) {
            return false;
        }

        $subQuery = $this
            ->createByShopIdQueryBuilder(self::MANUFACTURER_RESTRICTIONS_TABLE, $shopId)
            ->where('id_manufacturer = ' . $manufacturerId);

        return $this->exists($subQuery);
    }

    public function hasManufacturerRestrictions(?int $shopId = null): bool
    {
        $subQuery = $this->createByShopIdQueryBuilder(self::MANUFACTURER_RESTRICTIONS_TABLE, $shopId);

        return $this->exists($subQuery);
    }

    public function isAnyAttributeGroupRestricted(array $attributeGroupIds, ?int $shopId = null): bool
    {
        if ([] === $attributeGroupIds) {
            return false;
        }

        $subQuery = $this
            ->createByShopIdQueryBuilder(self::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE, $shopId)
            ->where(\sprintf('id_attribute_group IN (%s)', implode(',', array_map('intval', $attributeGroupIds))));

        return $this->exists($subQuery);
    }

    public function hasAttributeGroupRestrictions(?int $shopId = null): bool
    {
        $subQuery = $this->createByShopIdQueryBuilder(self::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE, $shopId);

        return $this->exists($subQuery);
    }

    public function isAnyFeatureRestricted(array $featureIds, ?int $shopId = null): bool
    {
        if ([] === $featureIds) {
            return false;
        }

        $subQuery = $this
            ->createByShopIdQueryBuilder(self::FEATURE_RESTRICTIONS_TABLE, $shopId)
            ->where(\sprintf('id_feature IN (%s)', implode(',', array_map('intval', $featureIds))));

        return $this->exists($subQuery);
    }

    public function hasFeatureRestrictions(?int $shopId = null): bool
    {
        $subQuery = $this->createByShopIdQueryBuilder(self::FEATURE_RESTRICTIONS_TABLE, $shopId);

        return $this->exists($subQuery);
    }

    public function getProductRestrictions(?int $shopId = null): ProductRestrictions
    {
        $categoryIds = $this->getRestrictedCategoryIds($shopId);
        $manufacturerIds = $this->getRestrictedManufacturerIds($shopId);
        $attributeGroupIds = $this->getRestrictedAttributeGroupIds($shopId);
        $featureIds = $this->getRestrictedFeatureIds($shopId);

        return (new ProductRestrictions())
            ->setCategoryIds($categoryIds)
            ->setManufacturerIds($manufacturerIds)
            ->setAttributeGroupIds($attributeGroupIds)
            ->setFeatureIds($featureIds);
    }

    public function updateProductRestrictions(ProductRestrictions $productRestrictions, ?int $shopId = null): void
    {
        $this->updateRestrictedCategories($productRestrictions->getCategoryIds(), $shopId);
        $this->updateRestrictedManufacturers($productRestrictions->getManufacturerIds(), $shopId);
        $this->updateRestrictedAttributes($productRestrictions->getAttributeGroupIds(), $shopId);
        $this->updateRestrictedFeatures($productRestrictions->getFeatureIds(), $shopId);
    }

    private function getRestrictedCategoryIds(?int $shopId): array
    {
        $qb = (new \DbQuery())
            ->select('id_category')
            ->from(self::CATEGORY_RESTRICTIONS_TABLE);

        return $this->getRestrictions($qb, $shopId);
    }

    private function getRestrictedManufacturerIds(?int $shopId): array
    {
        $qb = (new \DbQuery())
            ->select('id_manufacturer')
            ->from(self::MANUFACTURER_RESTRICTIONS_TABLE);

        return $this->getRestrictions($qb, $shopId);
    }

    private function getRestrictedAttributeGroupIds(?int $shopId): array
    {
        $qb = (new \DbQuery())
            ->select('id_attribute_group')
            ->from(self::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE);

        return $this->getRestrictions($qb, $shopId);
    }

    private function getRestrictedFeatureIds(?int $shopId): array
    {
        $qb = (new \DbQuery())
            ->select('id_feature')
            ->from(self::FEATURE_RESTRICTIONS_TABLE);

        return $this->getRestrictions($qb, $shopId);
    }

    /**
     * @param int[] $categoryIds
     */
    private function updateRestrictedCategories(array $categoryIds, ?int $shopId): void
    {
        $this->deleteByShopId(self::CATEGORY_RESTRICTIONS_TABLE, $shopId);

        if ([] === $categoryIds) {
            return;
        }

        $data = array_map(static function ($categoryId) use ($shopId): array {
            return [
                'id_category' => (int) $categoryId,
                'id_shop' => $shopId,
            ];
        }, $categoryIds);

        $this->connection->insert(self::CATEGORY_RESTRICTIONS_TABLE, $data);
    }

    /**
     * @param int[] $manufacturerIds
     */
    private function updateRestrictedManufacturers(array $manufacturerIds, ?int $shopId): void
    {
        $this->deleteByShopId(self::MANUFACTURER_RESTRICTIONS_TABLE, $shopId);

        if ([] === $manufacturerIds) {
            return;
        }

        $data = array_map(static function ($manufacturerId) use ($shopId): array {
            return [
                'id_manufacturer' => (int) $manufacturerId,
                'id_shop' => $shopId,
            ];
        }, $manufacturerIds);

        $this->connection->insert(self::MANUFACTURER_RESTRICTIONS_TABLE, $data);
    }

    /**
     * @param int[] $attributeGroupIds
     */
    private function updateRestrictedAttributes(array $attributeGroupIds, ?int $shopId): void
    {
        $this->deleteByShopId(self::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE, $shopId);

        if ([] === $attributeGroupIds) {
            return;
        }

        $data = array_map(static function ($attributeGroupId) use ($shopId): array {
            return [
                'id_attribute_group' => (int) $attributeGroupId,
                'id_shop' => $shopId,
            ];
        }, $attributeGroupIds);

        $this->connection->insert(self::ATTRIBUTE_GROUP_RESTRICTIONS_TABLE, $data);
    }

    /**
     * @param int[] $featureIds
     */
    private function updateRestrictedFeatures(array $featureIds, ?int $shopId): void
    {
        $this->deleteByShopId(self::FEATURE_RESTRICTIONS_TABLE, $shopId);

        if ([] === $featureIds) {
            return;
        }

        $data = array_map(static function ($featureId) use ($shopId): array {
            return [
                'id_feature' => (int) $featureId,
                'id_shop' => $shopId,
            ];
        }, $featureIds);

        $this->connection->insert(self::FEATURE_RESTRICTIONS_TABLE, $data);
    }

    private function exists(\DbQuery $qb): bool
    {
        $sql = 'SELECT EXISTS(' . $qb->select('1') . ')';

        return (bool) $this->connection->fetchOne($sql);
    }

    private function createByShopIdQueryBuilder(string $table, ?int $shopId): \DbQuery
    {
        $qb = (new \DbQuery())->from($table);

        if (null !== $shopId) {
            $qb->where('id_shop IS NULL OR id_shop = ' . $shopId);
        } else {
            $qb->where('id_shop IS NULL');
        }

        return $qb;
    }

    private function getRestrictions(\DbQuery $qb, ?int $shopId): array
    {
        if (null !== $shopId) {
            $qb->where('id_shop = ' . $shopId);
        } else {
            $qb->where('id_shop IS NULL');
        }

        return $this->connection->fetchFirstColumn((string) $qb);
    }

    private function deleteByShopId(string $table, ?int $shopId): void
    {
        $qb = (new \DbQuery())
            ->type('DELETE')
            ->from($table);

        if (null !== $shopId) {
            $qb->where('id_shop = ' . $shopId);
        } else {
            $qb->where('id_shop IS NULL');
        }

        $this->connection->executeStatement((string) $qb);
    }
}
