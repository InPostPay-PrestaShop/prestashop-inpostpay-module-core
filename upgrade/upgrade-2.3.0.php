<?php

use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Hook\Webservice\AddWebserviceResources;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InPostIziUpdater_2_3_0
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
        SymfonyCacheClearer::getInstance()->clear();

        return $this->registerHooks();
    }

    private function registerHooks(): bool
    {
        return $this->module->registerHook([
            AddWebserviceResources::HOOK_NAME,
        ]);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_3_0(Module $module): bool
{
    return InPostIziUpdater_2_3_0::create($module)->upgrade();
}
