<?php

use InPost\Izi\Upgrade\CacheClearer;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Hook\Admin\ActionAdminInPostConfirmedShipmentsControllerAfter;
use izi\prestashop\Hook\Admin\ActionAdminInPostConfirmedShipmentsControllerBefore;
use izi\prestashop\Hook\Common\ActionEmailSendBefore;
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

    /**
     * @var Db
     */
    private $db;

    public function __construct(Module $module, DatabaseInstaller $installer, Db $db)
    {
        $this->module = $module;
        $this->installer = $installer;
        $this->db = $db;
    }

    public static function create(Module $module): self
    {
        $db = Db::getInstance();
        $dbInstaller = new DatabaseInstaller(new Configuration($db), [
            new Version_2_1_0(new Connection($db)),
        ]);

        return new self($module, $dbInstaller, $db);
    }

    public function upgrade(): bool
    {
        CacheClearer::getInstance()->clear();
        $this->installer->install($this->module);

        return $this->registerHooks()
            && $this->renameCartRulesConfigKey();
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
            ActionEmailSendBefore::HOOK_NAME,
            ActionAdminInPostConfirmedShipmentsControllerAfter::HOOK_NAME,
            ActionAdminInPostConfirmedShipmentsControllerBefore::HOOK_NAME,
        ]);
    }

    private function renameCartRulesConfigKey(): bool
    {
        return $this->db->update('configuration', [
            'name' => 'INPOST_PAY_HAS_OMNIBUS_CART_RULES',
        ], 'name = "INPOST_PAY_OMNIBUS_CART_RULE_ID"');
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_1_0(Module $module): bool
{
    return InPostIziUpdater_2_1_0::create($module)->upgrade();
}
