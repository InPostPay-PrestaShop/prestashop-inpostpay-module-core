<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;
use InPost\Izi\Upgrade\CacheClearer;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Hook\Admin\ActionAdminCartRuleSaveAfter;
use izi\prestashop\Hook\Admin\ActionAdminControllerSetMedia;
use izi\prestashop\Hook\Admin\DisplayBackOfficeHeader;
use izi\prestashop\Installer\Database\Version_1_11_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/CacheClearer.php';
require_once __DIR__ . '/AssetsRemoverTrait.php';

class InPostIziUpdater_1_11_0
{
    use AssetsRemoverTrait;

    private const STALE_ASSETS = [
        'js/prestashopizi.109989890fd3720148dc.js',
        'css/product.6f69cd7d93f20866f321.css',
    ];

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

        return $this->registerHooks()
            && \Configuration::updateGlobalValue('INPOST_PAY_WIDGET_VERSION', '1.0')
            && $this->removeStaleAssets(self::STALE_ASSETS);
    }

    private function registerHooks(): bool
    {
        return $this->module->registerHook([
            ActionAdminCartRuleSaveAfter::HOOK_NAME,
            ActionAdminControllerSetMedia::HOOK_NAME,
            DisplayBackOfficeHeader::HOOK_NAME,
        ]);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_1_11_0(Module $module): bool
{
    $db = Db::getInstance();
    $dbInstaller = new DatabaseInstaller(new Configuration($db), [
        new Version_1_11_0(new Connection($db)),
    ]);

    return (new InPostIziUpdater_1_11_0($module, $dbInstaller))->upgrade();
}
