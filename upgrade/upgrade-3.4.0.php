<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/AssetsRemoverTrait.php';

class InPostIziUpdater_3_4_0
{
    use AssetsRemoverTrait;

    private const STALE_ASSETS = [
        'js/admin/nav-bar-fix.js',
    ];

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
        return $this->removeStaleAssets(self::STALE_ASSETS);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_3_4_0(Module $module): bool
{
    return InPostIziUpdater_3_4_0::create($module)->upgrade();
}
