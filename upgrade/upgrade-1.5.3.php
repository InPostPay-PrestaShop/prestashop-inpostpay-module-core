<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param InPostIzi $module
 *
 * @return bool
 */
function upgrade_module_1_5_3(Module $module)
{
    Tools::clearSf2Cache('prod');
    Tools::clearSf2Cache('dev');

    return true;
}
