<?php

use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Hook\HookExecutor;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InPostIziUpdater_3_3_0
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @var bool
     */
    private $disableHotProducts;

    public function __construct(Module $module, bool $disableHotProducts)
    {
        $this->module = $module;
        $this->disableHotProducts = $disableHotProducts;
    }

    public static function create(Module $module): self
    {
        return new self($module, !getenv('INPOST_IZI_HOT_PRODUCTS_ENABLED'));
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();

        return $this->unregisterHotProductHooks();
    }

    private function unregisterHotProductHooks(): bool
    {
        if (!$this->disableHotProducts) {
            return true;
        }

        $result = true;
        foreach (HookExecutor::HOT_PRODUCT_HOOKS as $name) {
            $result &= $this->module->unregisterHook($name);
        }

        return (bool) $result;
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_3_3_0(Module $module): bool
{
    return InPostIziUpdater_3_3_0::create($module)->upgrade();
}
