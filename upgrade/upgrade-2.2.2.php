<?php

use izi\prestashop\CacheClearer\SymfonyCacheClearer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_2_2(Module $module): bool
{
    SymfonyCacheClearer::getInstance()->clear();

    return true;
}
