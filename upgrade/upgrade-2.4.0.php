<?php

use izi\prestashop\CacheClearer\SymfonyCacheClearer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InPostIziUpdater_2_4_0
{
    /**
     * @var Db
     */
    private $db;

    public function __construct(\Db $db)
    {
        $this->db = $db;
    }

    public static function create(): self
    {
        return new self(\Db::getInstance());
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();

        return $this->renameProductRestrictedActionConfigKey();
    }


    private function renameProductRestrictedActionConfigKey(): bool
    {
        return $this->db->update('configuration', [
            'name' => 'INPOST_PAY_PRODUCT_RESTRICTED_ACTION',
        ], 'name = "INPOST_PAY_DISALLOW_ORDERING_RESTRICTED_PRODUCTS"');
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_4_0(Module $module): bool
{
    return InPostIziUpdater_2_4_0::create()->upgrade();
}
