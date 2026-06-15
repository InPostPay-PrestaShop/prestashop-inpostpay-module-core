<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Hook\HookExecutor;
use Symfony\Component\Finder\Finder;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/AssetsRemoverTrait.php';

class InPostIziUpdater_3_3_0
{
    use AssetsRemoverTrait;

    private const STALE_ASSETS = [
        'js/front/v2.2bd48b321391b1c84487.js',
    ];

    /**
     * @var bool
     */
    private $disableHotProducts;

    /**
     * @var string
     */
    private $logsDir;

    public function __construct(Module $module, bool $disableHotProducts, string $logsDir)
    {
        $this->module = $module;
        $this->disableHotProducts = $disableHotProducts;
        $this->logsDir = $logsDir;
    }

    public static function create(Module $module): self
    {
        return new self(
            $module,
            !getenv('INPOST_IZI_HOT_PRODUCTS_ENABLED'),
            _PS_ROOT_DIR_ . '/var/logs/inpost/'
        );
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();

        return $this->unregisterHotProductHooks()
            && $this->removeDeprecationLogs()
            && $this->removeStaleAssets(self::STALE_ASSETS);
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

    private function removeDeprecationLogs(): bool
    {
        $files = Finder::create()
            ->in($this->logsDir)
            ->name('izi.deprecations*.log')
            ->files();

        $this->getFileSystem()->remove($files);

        return true;
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_3_3_0(Module $module): bool
{
    return InPostIziUpdater_3_3_0::create($module)->upgrade();
}
