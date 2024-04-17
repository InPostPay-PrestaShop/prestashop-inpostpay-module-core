<?php

use izi\prestashop\Hook\Front\DisplayCheckoutSummaryTop;
use izi\prestashop\Hook\Front\DisplayCustomerAccountFormTop;
use izi\prestashop\Hook\Front\DisplayCustomerLoginFormAfter;
use izi\prestashop\Hook\Front\DisplayIziCartPreviewButton;
use izi\prestashop\Hook\Front\DisplayIziCheckoutButton;
use izi\prestashop\Hook\Front\DisplayProductActions;
use izi\prestashop\Hook\Front\DisplayProductAdditionalInfo;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param InPostIzi $module
 *
 * @return bool
 */
function upgrade_module_1_6_0(Module $module)
{
    Tools::clearSf2Cache('prod');
    Tools::clearSf2Cache('dev');

    $module->registerHook(DisplayProductActions::HOOK_NAME);
    $module->registerHook(DisplayProductAdditionalInfo::HOOK_NAME);
    $module->registerHook(DisplayCustomerLoginFormAfter::HOOK_NAME);
    $module->registerHook(DisplayCustomerAccountFormTop::HOOK_NAME);
    $module->registerHook(DisplayCheckoutSummaryTop::HOOK_NAME);
    $module->registerHook(DisplayIziCartPreviewButton::HOOK_NAME);
    $module->registerHook(DisplayIziCheckoutButton::HOOK_NAME);

    $productCardHook = DisplayProductActions::HOOK_NAME;

    if (version_compare(_PS_VERSION_, '1.7.6', '<')) {
        $productCardHook = DisplayProductAdditionalInfo::HOOK_NAME;
    }

    Configuration::updateValue('INPOST_PAY_PRODUCT_CARD_DISPLAY_HOOK', $productCardHook, false, 0, 0);

    return true;
}
