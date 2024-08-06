<?php

use InPost\Izi\Upgrade\CacheClearer;
use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Installer\Database\Version_1_9_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/CacheClearer.php';
require_once __DIR__ . '/ConfigUpdaterTrait.php';

class InPostIziUpdater_1_9_0
{
    use ConfigUpdaterTrait;

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

        return $this->installer->install($this->module);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_1_9_0(Module $module): bool
{
    $db = Db::getInstance();
    $dbInstaller = new DatabaseInstaller(new Configuration($db), [
        new Version_1_9_0($db),
    ]);

    return (new InPostIziUpdater_1_9_0($module, $dbInstaller))->upgrade();
}
