<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_15()
{
    foreach (['INPOST_PAY_payment_courier', 'INPOST_PAY_payment_courier_cod'] as $key) {
        $carrierId = \Configuration::get($key);
        if (!$carrierId || !\Validate::isLoadedObject($carrier = new \Carrier($carrierId))) {
            continue;
        }

        if (!\Configuration::updateValue($key, $carrier->id_reference)) {
            return false;
        }
    }

    return \Configuration::updateValue('INPOST_PAY_INITIAL_OS_ID', \Configuration::get('PS_OS_BANKWIRE'));
}
