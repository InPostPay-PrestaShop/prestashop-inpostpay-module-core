<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param \inpostizi $module
 *
 * @return bool
 */
function upgrade_module_1_3_15(\Module $module)
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

    return $module->registerHook('displayPaymentReturn')
        && $module->registerHook('actionAjaxDieCartControllerDisplayAjaxUpdateBefore')
        && $module->unregisterHook('actionPresentCart')
        && $module->unregisterHook('actionCartUpdateQuantityBefore')
        && \Configuration::updateValue('INPOST_PAY_INITIAL_OS_ID', \Configuration::get('PS_OS_BANKWIRE'));
}
