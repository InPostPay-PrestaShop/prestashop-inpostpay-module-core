<?php

use InPost\Izi\Upgrade\TranslationImporterTrait;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/TranslationImporterTrait.php';

class InPostIziUpdater_3_4_6
{
    use TranslationImporterTrait;

    public function __construct(Module $module, string $psVersion = _PS_VERSION_)
    {
        $this->module = $module;
        $this->psVersion = $psVersion;
    }

    public static function create(Module $module): self
    {
        return new self($module);
    }

    public function upgrade(): bool
    {
        return $this->importTranslations();
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_3_4_6(Module $module): bool
{
    return InPostIziUpdater_3_4_6::create($module)->upgrade();
}
