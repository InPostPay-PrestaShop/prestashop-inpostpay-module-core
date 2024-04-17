<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param InPostIzi $module
 *
 * @return bool
 */
function upgrade_module_1_5_7(Module $module)
{
    if (Tools::version_compare(_PS_VERSION_, '1.7.5')) {
        Media::clearCache();
    }

    return true;
}
