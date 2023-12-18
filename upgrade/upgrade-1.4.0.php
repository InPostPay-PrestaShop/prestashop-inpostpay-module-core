<?php

use izi\prestashop\Installer\Database\Version_1_4_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param \inpostizi $module
 *
 * @return bool
 */
function upgrade_module_1_4_0(\Module $module)
{
    $migration = new Version_1_4_0(\Db::getInstance());
    $dbInstaller = new DatabaseInstaller([$migration]);

    return $dbInstaller->install($module)
        && $module->registerHook('actionObjectCartDeleteBefore')
        && \Configuration::updateValue('INPOST_PAY_INITIAL_OS_ID', \Configuration::get('PS_OS_BANKWIRE'));
}
