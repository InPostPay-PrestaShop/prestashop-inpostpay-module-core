<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;
use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Installer\Database\Version_1_9_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/ConfigUpdaterTrait.php';
require_once __DIR__ . '/AssetsRemoverTrait.php';

class InPostIziUpdater_1_9_0
{
    use ConfigUpdaterTrait;
    use AssetsRemoverTrait;

    private const STALE_ASSETS = [
        'js/prestashopizi.972421cbefe1f8bf3243.js',
        'js/prestashopizi.972421cbefe1f8bf3243.js.map',
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
        SymfonyCacheClearer::getInstance()->clear();
        $this->installer->install($this->module);

        return $this->removeStaleAssets(self::STALE_ASSETS);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_1_9_0(Module $module): bool
{
    $db = Db::getInstance();
    $dbInstaller = new DatabaseInstaller(new Configuration($db), [
        new Version_1_9_0(new Connection($db)),
    ]);

    return (new InPostIziUpdater_1_9_0($module, $dbInstaller))->upgrade();
}
