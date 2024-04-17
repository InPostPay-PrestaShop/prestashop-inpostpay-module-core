<?php

use izi\prestashop\Hook\Front\DisplayPaymentReturn;
use izi\prestashop\Hook\Front\DisplayProductFooter;
use izi\prestashop\Hook\Front\DisplayShoppingCart;
use izi\prestashop\Hook\Front\DisplayShoppingCartFooter;
use izi\prestashop\Hook\HookExecutor;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param InPostIzi $module
 *
 * @return bool
 */
function upgrade_module_1_4_1(Module $module)
{
    $db = Db::getInstance();

    $sql = (new DbQuery())
        ->select('c.*, cl.*')
        ->from('configuration', 'c')
        ->innerJoin('configuration_lang', 'cl', 'cl.id_configuration = c.id_configuration')
        ->where('c.name LIKE "INPOST_PAY_OS_DESCRIPTION_%"');

    if ($data = $db->executeS($sql)) {
        $configIds = [];

        $mappings = [];
        foreach ($data as $row) {
            $configId = (int) $row['id_configuration'];
            $configIds[$configId] = $configId;

            if ('' === trim($row['value'])) {
                continue;
            }

            $osId = (int) filter_var($row['name'], FILTER_SANITIZE_NUMBER_INT);
            $mappings[(int) $row['id_shop_group']][(int) $row['id_shop']][(int) $row['id_lang']][$osId] = $row['value'];
        }

        foreach ($mappings as $shopGroupId => $mappingsByShop) {
            foreach ($mappingsByShop as $shopId => $mappingsByLang) {
                Configuration::updateValue('INPOST_PAY_OS_DESCRIPTION_MAP', array_map('json_encode', $mappingsByLang), false, $shopGroupId, $shopId);
            }
        }

        $db->delete('configuration', 'id_configuration IN (' . implode(',', $configIds) . ')');
        $db->delete('configuration_lang', 'id_configuration IN (' . implode(',', $configIds) . ')');
    }

    Configuration::updateGlobalValue('INPOST_PAY_THANK_YOU_DISPLAY', DisplayPaymentReturn::getHookName());

    return $module->unregisterHook(DisplayProductFooter::HOOK_NAME)
        && $module->unregisterHook(DisplayShoppingCart::HOOK_NAME)
        && $module->unregisterHook(DisplayShoppingCartFooter::HOOK_NAME)
        && $module->registerHook(HookExecutor::getHooksToInstall(_PS_VERSION_));
}
