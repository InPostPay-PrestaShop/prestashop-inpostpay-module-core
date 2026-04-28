<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Installer\Database\Version_2_6_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/AssetsRemoverTrait.php';

class InPostIziUpdater_2_6_0
{
    use AssetsRemoverTrait;

    private const STALE_ASSETS = [
        'js/front/v2.c1663f474810e7d47fc6.js',
    ];

    /**
     * @var Module
     */
    private $module;

    /**
     * @var DatabaseInstaller
     */
    private $installer;

    public function __construct(Module $module, DatabaseInstaller $installer)
    {
        $this->module = $module;
        $this->installer = $installer;
    }

    public static function create(Module $module): self
    {
        $db = Db::getInstance();
        $dbInstaller = new DatabaseInstaller(new Configuration($db), [
            new Version_2_6_0(new Connection($db)),
        ]);

        return new self($module, $dbInstaller);
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();
        $this->installer->install($this->module);

        return $this->removeStaleAssets(self::STALE_ASSETS);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_6_0(Module $module): bool
{
    return InPostIziUpdater_2_6_0::create($module)->upgrade();
}
