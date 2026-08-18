<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;
use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use InPost\Izi\Upgrade\TranslationImporterTrait;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/AssetsRemoverTrait.php';
require_once __DIR__ . '/ConfigUpdaterTrait.php';
require_once __DIR__ . '/TranslationImporterTrait.php';

class InPostIziUpdater_3_4_0
{
    use AssetsRemoverTrait;
    use ConfigUpdaterTrait;
    use TranslationImporterTrait;

    private const STALE_ASSETS = [
        'js/admin/nav-bar-fix.js',
    ];

    private const PAYMENT_CONFIG_KEYS = [
        'INPOST_PAY_ENABLE_ALL_PAYMENT_OPTIONS',
        'INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS',
    ];

    public function __construct(Module $module, \Db $db, string $psVersion = _PS_VERSION_)
    {
        $this->module = $module;
        $this->db = $db;
        $this->psVersion = $psVersion;
    }

    public static function create(Module $module): self
    {
        return new self($module, \Db::getInstance());
    }

    public function upgrade(): bool
    {
        return $this->removeStaleAssets(self::STALE_ASSETS)
            && $this->deleteConfigurationByKeys(self::PAYMENT_CONFIG_KEYS)
            && $this->importTranslations();
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_3_4_0(Module $module): bool
{
    return InPostIziUpdater_3_4_0::create($module)->upgrade();
}
