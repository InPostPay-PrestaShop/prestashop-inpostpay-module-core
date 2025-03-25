<?php

use InPost\Izi\Upgrade\CacheClearer;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Hook\Common\Product as ProductHooks;
use izi\prestashop\Installer\Database\Version_2_1_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/CacheClearer.php';

class InPostIziUpdater_2_1_0
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @var DatabaseInstaller
     */
    private $installer;

    public function __construct(Module $module, DatabaseInstaller $installer)
    {
        $this->module = $module;
        $this->installer = $installer;
    }

    public function upgrade(): bool
    {
        CacheClearer::getInstance()->clear();
        $this->installer->install($this->module);

        return $this->registerHooks();
    }

    private function registerHooks(): bool
    {
        return $this->module->registerHook([
            ProductHooks\ActionProductDeleteBefore::HOOK_NAME,
            ProductHooks\ActionProductDeleteAfter::HOOK_NAME,
            ProductHooks\ActionProductUpdateAfter::HOOK_NAME,
            ProductHooks\ActionCombinationDeleteBefore::HOOK_NAME,
            ProductHooks\ActionCombinationDeleteAfter::HOOK_NAME,
            ProductHooks\ActionCombinationUpdateAfter::HOOK_NAME,
            ProductHooks\ActionImageAddAfter::HOOK_NAME,
            ProductHooks\ActionImageDeleteAfter::HOOK_NAME,
            ProductHooks\ActionSpecificPriceAddAfter::HOOK_NAME,
            ProductHooks\ActionSpecificPriceUpdateAfter::HOOK_NAME,
            ProductHooks\ActionSpecificPriceDeleteAfter::HOOK_NAME,
            ProductHooks\ActionUpdateQuantity::HOOK_NAME,
        ]);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_1_0(Module $module): bool
{
    $db = Db::getInstance();
    $dbInstaller = new DatabaseInstaller(new Configuration($db), [
        new Version_2_1_0(new Connection($db)),
    ]);

    return (new InPostIziUpdater_2_1_0($module, $dbInstaller))->upgrade();
}
