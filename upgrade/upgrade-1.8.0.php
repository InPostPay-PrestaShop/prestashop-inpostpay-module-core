<?php

use izi\prestashop\CacheClearer\SymfonyCacheClearer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_1_8_0(Module $module): bool
{
    SymfonyCacheClearer::getInstance()->clear();

    return true;
}
