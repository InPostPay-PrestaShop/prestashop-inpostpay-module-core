<?php

use InPost\Izi\Upgrade\CacheClearer;
use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Configuration\DTO\Shipping\CarrierMapping;
use izi\prestashop\Configuration\DTO\Shipping\ServiceOptions;
use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;
use izi\prestashop\Configuration\DTO\Shipping\TimeOfWeek;
use izi\prestashop\Configuration\DTO\Shipping\TimeOfWeekRange;
use izi\prestashop\Configuration\DTO\Shipping\WeekDay;
use izi\prestashop\Hook\Front\ActionGetPaymentOptions;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/ConfigUpdaterTrait.php';
require_once __DIR__ . '/CacheClearer.php';

class InPostIziUpdater_1_5_5
{
    use ConfigUpdaterTrait;

    private const APM_CONFIG_MAP = [
        'INPOST_PAY_payment_apm' => 'mappingId',
        'INPOST_PAY_payment_apm_cod' => 'services.COD.cost',
        'INPOST_PAY_payment_apm_pww' => 'services.PWW.cost',
        'INPOST_PAY_payment_apm_pww_from_day' => 'services.PWW.availability.start.day',
        'INPOST_PAY_payment_apm_pww_from_time' => 'services.PWW.availability.start.time',
        'INPOST_PAY_payment_apm_pww_to_day' => 'services.PWW.availability.end.day',
        'INPOST_PAY_payment_apm_pww_to_time' => 'services.PWW.availability.end.time',
    ];

    private const COURIER_CONFIG_MAP = [
        'INPOST_PAY_payment_courier' => 'mappingId',
        'INPOST_PAY_payment_courier_cod' => 'services.COD.cost',
    ];

    private const UNMAPPED_CONFIG_KEYS = [
        'INPOST_PAY_payment_apm_cod_from_day',
        'INPOST_PAY_payment_apm_cod_from_time',
        'INPOST_PAY_payment_apm_cod_to_day',
        'INPOST_PAY_payment_apm_cod_to_time',
        'INPOST_PAY_payment_courier_cod_from_day',
        'INPOST_PAY_payment_courier_cod_to_day',
        'INPOST_PAY_payment_courier_cod_from_time',
        'INPOST_PAY_payment_courier_cod_to_time',
        'INPOST_PAY_payment_courier_pww',
        'INPOST_PAY_payment_courier_pww_from_day',
        'INPOST_PAY_payment_courier_pww_to_day',
        'INPOST_PAY_payment_courier_pww_from_time',
        'INPOST_PAY_payment_courier_pww_to_time',
    ];

    /**
     * @var Db
     */
    private $db;

    /**
     * @var Module
     */
    private $module;

    public function __construct(Db $db, Module $module)
    {
        $this->db = $db;
        $this->module = $module;
    }

    public function upgrade(): bool
    {
        CacheClearer::getInstance()->clear();

        if (!$this->updateShippingConfigStructure()) {
            return false;
        }

        $this->module->unregisterHook(ActionGetPaymentOptions::HOOK_NAME);

        return true;
    }

    private function updateShippingConfigStructure(): bool
    {
        return $this->updateApmShippingConfigStructure()
            && $this->updateCourierShippingConfigStructure()
            && $this->deleteConfigurationByKeys(self::UNMAPPED_CONFIG_KEYS);
    }

    private function updateApmShippingConfigStructure(): bool
    {
        $configs = $this->getShippingOptionsConfigs(self::APM_CONFIG_MAP, DeliveryType::Apm());

        return $this->setJsonConfigValues('INPOST_PAY_APM_SHIPPING_OPTIONS', $configs)
            && $this->deleteConfigurationByKeys(array_keys(self::APM_CONFIG_MAP));
    }

    private function updateCourierShippingConfigStructure(): bool
    {
        $configs = $this->getShippingOptionsConfigs(self::COURIER_CONFIG_MAP, DeliveryType::Courier());

        return $this->setJsonConfigValues('INPOST_PAY_COURIER_SHIPPING_OPTIONS', $configs)
            && $this->deleteConfigurationByKeys(array_keys(self::COURIER_CONFIG_MAP));
    }

    private function getShippingOptionsConfigs(array $map, DeliveryType $deliveryType): array
    {
        if ([] === $data = $this->getConfigDataByKeys(array_keys($map))) {
            return [];
        }

        $shippingOptions = [];
        $dataByShopGroup = $this->groupConfigValuesByShop($data);

        foreach ($dataByShopGroup as $shopGroupId => $dataByShop) {
            foreach ($dataByShop as $shopId => $data) {
                $config = $this->reorganizeData($data, $map);

                $shippingOptions[$shopGroupId][$shopId] = new ShippingOptions(
                    $this->getCarrierMappings($config['mappingId'], $deliveryType),
                    $this->getOptionalServices($config['services'], $deliveryType)
                );
            }
        }

        return $shippingOptions;
    }

    private function getCarrierMappings(?int $referenceId, DeliveryType $deliveryType): array
    {
        $mappings = [];

        foreach (ServiceCode::getAvailableCombinations($deliveryType) as $serviceCodes) {
            $mappings[] = new CarrierMapping($referenceId, $serviceCodes);
        }

        return $mappings;
    }

    private function getOptionalServices(array $config, DeliveryType $deliveryType): array
    {
        $services = [];

        foreach ($deliveryType->getAvailableServiceCodes() as $serviceCode) {
            $key = $serviceCode->value;
            $services[] = $this->getServiceOptions($config[$key], $serviceCode);
        }

        return $services;
    }

    private function getServiceOptions(array $config, ServiceCode $serviceCode): ServiceOptions
    {
        return new ServiceOptions(
            $serviceCode,
            isset($config['cost']) ? (float) $config['cost'] : null,
            $serviceCode->isAvailabilityTimeDependent() ? $this->getTimeRange($config['availability']) : null
        );
    }

    private function getTimeRange(array $config): TimeOfWeekRange
    {
        return new TimeOfWeekRange(
            $this->getTimeOfWeek($config['start']),
            $this->getTimeOfWeek($config['end'])
        );
    }

    private function getTimeOfWeek(array $config): TimeOfWeek
    {
        $weekDay = isset($config['day']) ? WeekDay::tryFrom($config['day'] + 1) : null;
        $time = isset($config['time'])
            ? DateTimeImmutable::createFromFormat('G', (int) $config['time'])
            : null;

        return new TimeOfWeek($weekDay, $time);
    }

    private function reorganizeData(array $data, array $map): array
    {
        $config = [];

        foreach ($map as $key => $path) {
            $value = $data[$key] ?? null;

            if (false === strpos($path, '.')) {
                $config[$path] = $value;
            } else {
                $path = explode('.', $path);
                while ($part = array_pop($path)) {
                    $value = [$part => $value];
                }
                $config = array_merge_recursive($config, $value);
            }
        }

        return $config;
    }
}

/**
 * @param InPostIzi $module
 *
 * @return bool
 */
function upgrade_module_1_5_5(Module $module)
{
    return (new InPostIziUpdater_1_5_5(Db::getInstance(), $module))->upgrade();
}
