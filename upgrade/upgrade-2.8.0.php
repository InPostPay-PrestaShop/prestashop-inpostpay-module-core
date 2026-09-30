<?php

use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Hook\Admin\ActionAdminCartRulesListingFieldsModifier;
use izi\prestashop\Hook\Common\ActionObjectOrderAddAfter;
use izi\prestashop\Hook\Common\ActionObjectOrderCartRuleAddBefore;
use izi\prestashop\Hook\Common\DisplayPDFInvoice;
use izi\prestashop\Installer\Database\Version_2_8_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/ConfigUpdaterTrait.php';

class InPostIziUpdater_2_8_0
{
    use ConfigUpdaterTrait;

    private const PAYMENT_CONFIG_KEYS = [
        'INPOST_PAY_ENABLE_ALL_PAYMENT_OPTIONS',
        'INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS',
    ];

    private const NEW_HOOK_NAMES = [
        ActionAdminCartRulesListingFieldsModifier::HOOK_NAME,
        DisplayPDFInvoice::HOOK_NAME,
        ActionObjectOrderAddAfter::HOOK_NAME,
        ActionObjectOrderCartRuleAddBefore::HOOK_NAME,
    ];

    /**
     * @var Module
     */
    private $module;

    /**
     * @var DatabaseInstaller
     */
    private $installer;

    public function __construct(Module $module, \Db $db, DatabaseInstaller $installer)
    {
        $this->module = $module;
        $this->db = $db;
        $this->installer = $installer;
    }

    public static function create(Module $module): self
    {
        $db = Db::getInstance();
        $dbInstaller = new DatabaseInstaller(new Configuration($db), [
            new Version_2_8_0(new Connection($db)),
        ]);

        return new self($module, $db, $dbInstaller);
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();
        $this->installer->install($this->module);

        return $this->module->registerHook(self::NEW_HOOK_NAMES)
            && $this->deleteConfigurationByKeys(self::PAYMENT_CONFIG_KEYS);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_8_0(Module $module): bool
{
    return InPostIziUpdater_2_8_0::create($module)->upgrade();
}
