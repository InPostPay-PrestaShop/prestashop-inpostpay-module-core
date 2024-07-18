<?php

use InPost\Izi\Upgrade\CacheClearer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/CacheClearer.php';

/**
 * @param InPostIzi $module
 */
function upgrade_module_1_8_0(Module $module): bool
{
    CacheClearer::getInstance()->clear();

    return true;
}
