<?php

use InPost\Izi\Upgrade\CacheClearer;
use InPost\Izi\Upgrade\ConfigUpdaterTrait;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/CacheClearer.php';
require_once __DIR__ . '/ConfigUpdaterTrait.php';

class InPostIziUpdater_1_7_0
{
    use ConfigUpdaterTrait;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function upgrade(): bool
    {
        CacheClearer::getInstance()->clear();

        return $this->fixAvailablePaymentOptionsConfig();
    }

    private function fixAvailablePaymentOptionsConfig(): bool
    {
        $configs = $this->getAvailablePaymentOptionsConfigs();

        return $this->setJsonConfigValues('INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS', $configs);
    }

    private function getAvailablePaymentOptionsConfigs(): array
    {
        if ([] === $data = $this->getConfigDataByKeys(['INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS'])) {
            return [];
        }

        $configs = [];
        $dataByShopGroup = $this->groupConfigValuesByShop($data);

        foreach ($dataByShopGroup as $shopGroupId => $dataByShop) {
            foreach ($dataByShop as $shopId => $data) {
                if (null === $value = $data['INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS']) {
                    continue;
                }

                $data = json_decode($value, true);

                if (!is_array($data) || $data === $config = array_values($data)) {
                    continue;
                }

                $configs[$shopGroupId][$shopId] = $config;
            }
        }

        return $configs;
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_1_7_0(Module $module): bool
{
    return (new InPostIziUpdater_1_7_0(Db::getInstance()))->upgrade();
}
