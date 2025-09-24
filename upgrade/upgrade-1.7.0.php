<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;
use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/ConfigUpdaterTrait.php';
require_once __DIR__ . '/AssetsRemoverTrait.php';

class InPostIziUpdater_1_7_0
{
    use ConfigUpdaterTrait;
    use AssetsRemoverTrait;

    private const STALE_ASSETS = [
        'js/prestashopizi.f8bd8f9189c554596cce.js',
        'js/prestashopizi.f8bd8f9189c554596cce.js.map',
    ];

    public function __construct(Db $db, Module $module)
    {
        $this->db = $db;
        $this->module = $module;
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();

        return $this->fixAvailablePaymentOptionsConfig()
            && $this->removeStaleAssets(self::STALE_ASSETS);
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
    $db = Db::getInstance();

    return (new InPostIziUpdater_1_7_0($db, $module))->upgrade();
}
