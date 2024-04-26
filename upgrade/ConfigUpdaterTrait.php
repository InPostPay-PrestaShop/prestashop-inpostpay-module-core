<?php

declare(strict_types=1);

namespace InPost\Izi\Upgrade;

trait ConfigUpdaterTrait
{
    /**
     * @var \Db
     */
    private $db;

    private function getConfigDataByKeys(array $keys): array
    {
        $sql = (new \DbQuery())
            ->select('c.*')
            ->from('configuration', 'c')
            ->where(sprintf('c.name IN ("%s")', implode('","', $keys)));

        return $this->db->executeS($sql) ?: [];
    }

    private function groupConfigValuesByShop(array $data): array
    {
        $dataByShopGroup = [];

        foreach ($data as $row) {
            $dataByShopGroup[(int) $row['id_shop_group']][(int) $row['id_shop']][$row['name']] = $row['value'];
        }

        return $dataByShopGroup;
    }

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

    private function deleteConfigurationByKeys(array $keys): bool
    {
        return $this->db->delete('configuration', 'name IN ("' . implode('","', $keys) . '")');
    }
}
