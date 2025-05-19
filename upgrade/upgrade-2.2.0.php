<?php

use InPost\Izi\Upgrade\CacheClearer;
use izi\prestashop\Hook\Admin\ActionAdminInPostConfirmedShipmentsControllerAfter;
use izi\prestashop\Hook\Admin\ActionAdminInPostConfirmedShipmentsControllerBefore;
use izi\prestashop\Hook\Common\ActionEmailSendBefore;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/CacheClearer.php';

class InPostIziUpdater_2_2_0
{
    /**
     * @var Module
     */
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public static function create(Module $module): self
    {
        return new self($module);
    }

    public function upgrade(): bool
    {
        CacheClearer::getInstance()->clear();

        return $this->registerHooks();
    }

    private function registerHooks(): bool
    {
        return $this->module->registerHook([
            ActionEmailSendBefore::HOOK_NAME,
            ActionAdminInPostConfirmedShipmentsControllerAfter::HOOK_NAME,
            ActionAdminInPostConfirmedShipmentsControllerBefore::HOOK_NAME,
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
