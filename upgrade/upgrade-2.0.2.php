<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/AssetsRemoverTrait.php';

class InPostIziUpdater_2_0_2
{
    use AssetsRemoverTrait;

    private const STALE_ASSETS = [
        'js/front/v2.bcd9fca64e00b4978766.js',
    ];

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function upgrade(): bool
    {
        return $this->removeStaleAssets(self::STALE_ASSETS);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_0_2(Module $module): bool
{
    return (new InPostIziUpdater_2_0_2($module))->upgrade();
}
