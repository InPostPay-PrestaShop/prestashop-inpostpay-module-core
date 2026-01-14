<?php

use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Hook\Admin\ActionAdminInPostConfirmedShipmentsControllerAfter;
use izi\prestashop\Hook\Admin\ActionAdminInPostConfirmedShipmentsControllerBefore;
use izi\prestashop\Hook\Common\ActionEmailSendBefore;
use izi\prestashop\Hook\Front\DisplayHeader;
use izi\prestashop\Installer\Database\Version_2_2_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InPostIziUpdater_2_2_0
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
            new Version_2_2_0(new Connection($db)),
        ], new Configuration($db));

        return new self($module, $dbInstaller);
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();
        $this->installer->install($this->module);

        return $this->registerHooks();
    }

    private function registerHooks(): bool
    {
        return $this->module->registerHook([
            ActionEmailSendBefore::HOOK_NAME,
            ActionAdminInPostConfirmedShipmentsControllerAfter::HOOK_NAME,
            ActionAdminInPostConfirmedShipmentsControllerBefore::HOOK_NAME,
            DisplayHeader::HOOK_NAME,
        ]);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_2_0(Module $module): bool
{
    return InPostIziUpdater_2_2_0::create($module)->upgrade();
}
