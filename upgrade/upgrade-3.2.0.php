<?php

use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Installer\Database\Version_3_2_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InPostIziUpdater_3_2_0
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

    public static function create(Module $module): self
    {
        $db = Db::getInstance();
        $dbInstaller = new DatabaseInstaller([
            new Version_3_2_0(new Connection($db)),
        ], new Configuration($db));

        return new self($module, $dbInstaller);
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();
        $this->installer->install($this->module);

        return true;
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_3_2_0(Module $module): bool
{
    return InPostIziUpdater_3_2_0::create($module)->upgrade();
}
