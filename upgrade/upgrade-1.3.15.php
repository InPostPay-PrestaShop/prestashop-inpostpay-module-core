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
        if (!$carrierId = \Configuration::get($key)) {
            continue;
        }

        $carrier = new \Carrier($carrierId);

        if (!\Configuration::updateValue($key, $carrier->id_reference)) {
            return false;
        }
    }

    return $module->registerHook('displayPaymentReturn')
        && $module->registerHook('actionAjaxDieCartControllerDisplayAjaxUpdateBefore')
        && $module->unregisterHook('actionPresentCart')
        && $module->unregisterHook('actionCartUpdateQuantityBefore');
}
