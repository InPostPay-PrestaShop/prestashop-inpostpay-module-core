<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param \InPostIzi $module
 *
 * @return bool
 */
function upgrade_module_1_4_2(\Module $module)
{
    $db = \Db::getInstance();

    $db->update('configuration', [
        'value' => [
            'type' => 'sql',
            'value' => '(value = 2)',
        ],
    ], 'name = "INPOST_PAY_show_izi"');

    return true;
}
