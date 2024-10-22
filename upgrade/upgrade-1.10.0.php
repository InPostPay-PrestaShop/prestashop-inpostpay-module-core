<?php

use InPost\Izi\Upgrade\CacheClearer;
use izi\prestashop\Hook\Common\ActionObjectOrderUpdateAfter;
use izi\prestashop\Hook\Common\ActionObjectOrderUpdateBefore;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/CacheClearer.php';

class InPostIziUpdater_1_10_0
{
    /**
     * @var Module
     */
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function upgrade(): bool
    {
        CacheClearer::getInstance()->clear();

        return $this->registerHooks()
            && $this->initCodPaymentOrderStateConfig();
    }

    private function registerHooks(): bool
    {
        $hooks = [
            ActionObjectOrderUpdateBefore::HOOK_NAME,
            ActionObjectOrderUpdateAfter::HOOK_NAME,
        ];

        return $this->module->registerHook($hooks);
    }

    private function initCodPaymentOrderStateConfig(): bool
    {
        if (0 >= $orderStateId = $this->getCodPaymentOrderStateId()) {
            return true;
        }

        return \Configuration::updateGlobalValue('INPOST_PAY_COD_OS_ID', $orderStateId);
    }

    private function getCodPaymentOrderStateId(): int
    {
        $orderStateId = (int) Configuration::get('PS_OS_COD_VALIDATION');

        if (Validate::isLoadedObject(new OrderState($orderStateId))) {
            return $orderStateId;
        }

        return (int) Configuration::get('INPOST_PAY_INITIAL_OS_ID');
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_1_10_0(Module $module): bool
{
    return (new InPostIziUpdater_1_10_0($module))->upgrade();
}
