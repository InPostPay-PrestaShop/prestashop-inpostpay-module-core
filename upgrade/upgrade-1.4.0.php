<?php

use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Installer\Database\Version_1_4_0;
use izi\prestashop\Installer\DatabaseInstaller;
use izi\prestashop\View\Widget\Variant;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param InPostIzi $module
 *
 * @return bool
 */
function upgrade_module_1_4_0(Module $module)
{
    $db = Db::getInstance();
    $migration = new Version_1_4_0(new Connection($db));
    $dbInstaller = new DatabaseInstaller(new Configuration(), [$migration]);

    $db->delete('configuration', 'name LIKE "INPOST_PAY_status_translation_%"');
    $db->execute(sprintf('
        UPDATE `%sconfiguration`
        SET `value` = (`value` = "dark")
        WHERE `name` IN ("INPOST_PAY_background_cart", "INPOST_PAY_background_details")
    ', _DB_PREFIX_));
    $db->execute(sprintf('
        UPDATE `%sconfiguration`
        SET `value` = IF(`value` = "yellow", "%s", "%s")
        WHERE `name` IN ("INPOST_PAY_variant_cart", "INPOST_PAY_variant_details")
    ', _DB_PREFIX_, Variant::Primary()->value, Variant::Secondary()->value));

    $dbInstaller->install($module);

    return $module->registerHook('actionObjectCartDeleteBefore')
        && $module->registerHook('actionObjectInPostShipmentModelUpdateBefore')
        && \Configuration::updateGlobalValue('INPOST_PAY_INITIAL_OS_ID', \Configuration::get('PS_OS_BANKWIRE'));
}
