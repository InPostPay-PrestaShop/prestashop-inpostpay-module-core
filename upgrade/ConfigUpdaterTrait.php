<?php

declare(strict_types=1);

namespace InPost\Izi\Upgrade;

trait ConfigUpdaterTrait
{
    /**
     * @var \Db
     */
    private $db;

    /**
     * @param string[] $keys
     */
    private function getConfigDataByKeys(array $keys): array
    {
        $sql = (new \DbQuery())
            ->select('c.*')
            ->from('configuration', 'c')
            ->where(\sprintf('c.name IN ("%s")', array_map('pSQL', $keys)));

        return $this->db->executeS($sql) ?: [];
    }

    /**
     * @return array<int, array<int, array<string, mixed>>> values by shop group ID, shop ID and config key
     */
    private function groupConfigValuesByShop(array $data): array
    {
        $dataByShopGroup = [];

        foreach ($data as $row) {
            $dataByShopGroup[(int) $row['id_shop_group']][(int) $row['id_shop']][$row['name']] = $row['value'];
        }

        return $dataByShopGroup;
    }

    /**
     * @param array<int, array<int, mixed>> $dataByShopGroup unencoded config values by shop group ID and shop ID
     */
    private function setJsonConfigValues(string $key, array $dataByShopGroup): bool
    {
        foreach ($dataByShopGroup as $shopGroupId => $dataByShop) {
            foreach ($dataByShop as $shopId => $data) {
                $value = json_encode($data);
                if (!\Configuration::updateValue($key, $value, false, $shopGroupId, $shopId)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string[] $keys
     */
    private function deleteConfigurationByKeys(array $keys): bool
    {
        return $this->db->delete('configuration', 'name IN ("' . implode('","', array_map('pSQL', $keys)) . '")');
    }
}
