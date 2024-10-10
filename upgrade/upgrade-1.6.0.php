<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;
use InPost\Izi\Upgrade\CacheClearer;
use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Hook\Admin\DisplayAdminOrderLeft;
use izi\prestashop\Hook\Common\ActionCartSave;
use izi\prestashop\Hook\Common\ActionCartUpdateAfter;
use izi\prestashop\Hook\Common\ActionOrderStatusPostUpdate;
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

require_once __DIR__ . '/ConfigUpdaterTrait.php';
require_once __DIR__ . '/CacheClearer.php';
require_once __DIR__ . '/AssetsRemoverTrait.php';

class InPostIziUpdater_1_6_0
{
    use ConfigUpdaterTrait;
    use AssetsRemoverTrait;

    public function __construct(Db $db, Module $module)
    {
        $this->db = $db;
        $this->module = $module;
    }

    public function upgrade(): bool
    {
        CacheClearer::getInstance()->clear();

        return $this->registerHooks()
            && $this->updateAvailablePaymentOptionsConfig()
            && $this->removeStaleAssets([
                'js/prestashopizi.js',
            ]);
    }

    private function registerHooks(): bool
    {
        $productCardHook = $this->getDefaultProductCardHook();

        if (!Configuration::updateGlobalValue('INPOST_PAY_PRODUCT_CARD_DISPLAY_HOOK', $productCardHook)) {
            return false;
        }

        return $this->module->unregisterHook(ActionCartSave::HOOK_NAME)
            && $this->module->registerHook($this->getHooksToInstall());
    }

    private function getDefaultProductCardHook(): string
    {
        if (
            DisplayProductActions::getVersionRange()->contains(_PS_VERSION_)
            && !$this->module->isRegisteredInHook(DisplayProductAdditionalInfo::HOOK_NAME)
        ) {
            return DisplayProductActions::HOOK_NAME;
        }

        return DisplayProductAdditionalInfo::HOOK_NAME;
    }

    private function updateAvailablePaymentOptionsConfig(): bool
    {
        $map = [
            'INPOST_PAY_payment_aion' => PaymentType::getAvailableByDefaultPaymentOptions(),
            'INPOST_PAY_payment_inpost' => PaymentType::getCarrierProvidedPaymentOptions(),
        ];

        $configs = $this->getAvailablePaymentOptionsConfigs($map);

        return $this->setJsonConfigValues('INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS', $configs)
            && $this->deleteConfigurationByKeys(array_keys($map));
    }

    private function getAvailablePaymentOptionsConfigs(array $map): array
    {
        if ([] === $data = $this->getConfigDataByKeys(array_keys($map))) {
            return [];
        }

        $paymentOptions = [];
        $dataByShopGroup = $this->groupConfigValuesByShop($data);

        foreach ($dataByShopGroup as $shopGroupId => $dataByShop) {
            foreach ($dataByShop as $shopId => $data) {
                if ([] === $data = array_filter($data)) {
                    continue;
                }

                $types = [];

                foreach ($data as $key => $ignored) {
                    $types[] = $map[$key];
                }

                $paymentOptions[$shopGroupId][$shopId] = array_merge(...$types);
            }
        }

        return $paymentOptions;
    }

    private function getHooksToInstall(): array
    {
        $hooks = [
            DisplayCustomerLoginFormAfter::HOOK_NAME,
            DisplayCustomerAccountFormTop::HOOK_NAME,
            DisplayCheckoutSummaryTop::HOOK_NAME,
            DisplayIziCartPreviewButton::HOOK_NAME,
            DisplayIziCheckoutButton::HOOK_NAME,
            ActionCartUpdateAfter::HOOK_NAME,
            ActionOrderStatusPostUpdate::HOOK_NAME,
        ];

        if (DisplayAdminOrderLeft::getVersionRange()->contains(_PS_VERSION_)) {
            $hooks[] = DisplayAdminOrderLeft::HOOK_NAME;
        }

        return $hooks;
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_1_6_0(Module $module): bool
{
    return (new InPostIziUpdater_1_6_0(Db::getInstance(), $module))->upgrade();
}
