<?php

use InPost\Izi\Upgrade\CacheClearer;
use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use Symfony\Component\Filesystem\Filesystem;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/CacheClearer.php';
require_once __DIR__ . '/ConfigUpdaterTrait.php';

class InPostIziUpdater_1_7_0
{
    use ConfigUpdaterTrait;

    /**
     * @var Module
     */
    private $module;

    public function __construct(Db $db, Module $module)
    {
        $this->db = $db;
        $this->module = $module;
    }

    public function upgrade(): bool
    {
        CacheClearer::getInstance()->clear();

        return $this->fixAvailablePaymentOptionsConfig()
            && $this->removeStaleAssets();
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

    private function removeStaleAssets(): bool
    {
        $basePath = sprintf('%s/views', rtrim($this->module->getLocalPath(), '/'));
        $files = array_map(static function (string $file) use ($basePath): string {
            return sprintf('%s/%s', $basePath, $file);
        }, [
            'js/prestashopizi.f8bd8f9189c554596cce.js',
            'js/prestashopizi.f8bd8f9189c554596cce.js.map',
        ]);

        (new Filesystem())->remove($files);

        return true;
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
