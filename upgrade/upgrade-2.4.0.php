<?php

use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Hook\Admin\Product\ActionAfterUpdateProductFormHandler;
use izi\prestashop\Hook\Admin\Product\ActionProductFormBuilderModifier;
use izi\prestashop\Hook\Legacy\Admin\Product\ActionAdminProductsSaveAfter;
use izi\prestashop\Hook\Legacy\Admin\Product\DisplayAdminProductsExtra;
use izi\prestashop\Hook\Legacy\Admin\Product\DisplayAdminProductsOptionsStepBottom;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Installer\Database\Version_2_4_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InPostIziUpdater_2_4_0
{
    private const NEW_HOOKS = [
        DisplayAdminProductsOptionsStepBottom::class,
        DisplayAdminProductsExtra::class,
        ActionAdminProductsSaveAfter::class,
        ActionProductFormBuilderModifier::class,
        ActionAfterUpdateProductFormHandler::class,
    ];

    /**
     * @var Module
     */
    private $module;

    /**
     * @var Db
     */
    private $db;

    /**
     * @var DatabaseInstaller
     */
    private $installer;

    /**
     * @var string
     */
    private $psVersion;

    public function __construct(Module $module, Db $db, DatabaseInstaller $installer, string $psVersion)
    {
        $this->module = $module;
        $this->db = $db;
        $this->installer = $installer;
        $this->psVersion = $psVersion;
    }

    public static function create(Module $module): self
    {
        $db = Db::getInstance();
        $dbInstaller = new DatabaseInstaller(new Configuration($db), [
            new Version_2_4_0(new Connection($db)),
        ]);

        return new self($module, $db, $dbInstaller, _PS_VERSION_);
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();
        $this->installer->install($this->module);

        return $this->registerHooks()
            && $this->renameProductRestrictedActionConfigKey();
    }

    private function registerHooks(): bool
    {
        $hookNames = [];

        /** @var class-string<PrestaShopVersionAwareHookInterface> $hook */
        foreach (self::NEW_HOOKS as $hook) {
            if (!$hook::getVersionRange()->contains($this->psVersion)) {
                continue;
            }

            $hookNames[] = $hook::getHookName();
        }

        return (bool) $this->module->registerHook($hookNames);
    }

    private function renameProductRestrictedActionConfigKey(): bool
    {
        return $this->db->update('configuration', [
            'name' => 'INPOST_PAY_PRODUCT_RESTRICTED_ACTION',
        ], 'name = "INPOST_PAY_DISALLOW_ORDERING_RESTRICTED_PRODUCTS"');
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_4_0(Module $module): bool
{
    return InPostIziUpdater_2_4_0::create($module)->upgrade();
}
