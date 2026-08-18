<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;
use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Hook\Admin\ActionAdminCartRulesListingFieldsModifier;
use izi\prestashop\Hook\Common\ActionObjectOrderAddAfter;
use izi\prestashop\Hook\Common\ActionObjectOrderCartRuleAddBefore;
use izi\prestashop\Hook\Common\CartRule\ActionApplyCartRule;
use izi\prestashop\Hook\Common\CartRule\ActionGetCartRuleContextualValue;
use izi\prestashop\Hook\Common\CartRule\ActionValidateCartRule;
use izi\prestashop\Hook\Common\DisplayPDFInvoice;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Installer\Database\Version_3_4_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/AssetsRemoverTrait.php';
require_once __DIR__ . '/ConfigUpdaterTrait.php';

class InPostIziUpdater_3_4_0
{
    use AssetsRemoverTrait;
    use ConfigUpdaterTrait;

    private const STALE_ASSETS = [
        'js/admin/nav-bar-fix.js',
    ];

    private const PAYMENT_CONFIG_KEYS = [
        'INPOST_PAY_ENABLE_ALL_PAYMENT_OPTIONS',
        'INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS',
    ];

    private const NEW_COMMON_HOOK_NAMES = [
        ActionAdminCartRulesListingFieldsModifier::HOOK_NAME,
        DisplayPDFInvoice::HOOK_NAME,
    ];

    private const NEW_PS_VERSION_AWARE_HOOKS = [
        ActionApplyCartRule::class,
        ActionGetCartRuleContextualValue::class,
        ActionValidateCartRule::class,
        ActionObjectOrderAddAfter::class,
        ActionObjectOrderCartRuleAddBefore::class,
    ];

    /**
     * @var DatabaseInstaller
     */
    private $installer;

    /**
     * @var string
     */
    private $psVersion;

    public function __construct(Module $module, \Db $db, DatabaseInstaller $installer, string $psVersion)
    {
        $this->module = $module;
        $this->db = $db;
        $this->installer = $installer;
        $this->psVersion = $psVersion;
    }

    public static function create(Module $module): self
    {
        $db = Db::getInstance();
        $dbInstaller = new DatabaseInstaller([
            new Version_3_4_0(new Connection($db)),
        ], new Configuration($db));

        return new self($module, $db, $dbInstaller, _PS_VERSION_);
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();
        $this->installer->install($this->module);

        return $this->registerHooks()
            && $this->removeStaleAssets(self::STALE_ASSETS)
            && $this->deleteConfigurationByKeys(self::PAYMENT_CONFIG_KEYS);
    }

    private function registerHooks(): bool
    {
        $hookNames = self::NEW_COMMON_HOOK_NAMES;

        /** @var class-string<PrestaShopVersionAwareHookInterface> $hook */
        foreach (self::NEW_PS_VERSION_AWARE_HOOKS as $hook) {
            if (!$hook::getVersionRange()->contains($this->psVersion)) {
                continue;
            }

            $hookNames[] = $hook::getHookName();
        }

        return (bool) $this->module->registerHook($hookNames);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_3_4_0(Module $module): bool
{
    return InPostIziUpdater_3_4_0::create($module)->upgrade();
}
