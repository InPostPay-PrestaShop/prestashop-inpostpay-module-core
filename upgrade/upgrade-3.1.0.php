<?php

use InPost\Izi\Upgrade\FileRemoverTrait;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/FileRemoverTrait.php';

class InPostIziUpdater_3_1_0
{
    use FileRemoverTrait;

    /**
     * No BC break: previously marked as internal.
     */
    private const CLASSES_TO_REMOVE = [
        izi\prestashop\MerchantApi\Model\Basket\Response\BasketTrait::class,
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
        return $this->removeClasses(self::CLASSES_TO_REMOVE);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_3_1_0(Module $module): bool
{
    return InPostIziUpdater_3_1_0::create($module)->upgrade();
}
