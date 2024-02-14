<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Configuration\DTO\Day;
use izi\prestashop\Configuration\DTO\Hour;
use izi\prestashop\Configuration\DTO\Shipping;
use izi\prestashop\Configuration\Factory\DayFactoryInterface;
use izi\prestashop\Configuration\Factory\HourFactoryInterface;
use izi\prestashop\ObjectModel\Repository\CarrierRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

/**
 * @todo store serialized delivery option config under single key
 */
final class ShippingConfiguration implements ShippingConfigurationInterface, PersistentConfigurationInterface
{
    private const APM_ID = 'INPOST_PAY_payment_apm';
    private const APM_PWW_PRICE = 'INPOST_PAY_payment_apm_pww';
    private const APM_PWW_AVAILABLE_FROM_DAY = 'INPOST_PAY_payment_apm_pww_from_day';
    private const APM_PWW_AVAILABLE_TO_DAY = 'INPOST_PAY_payment_apm_pww_to_day';
    private const APM_PWW_AVAILABLE_FROM_HOUR = 'INPOST_PAY_payment_apm_pww_from_time';
    private const APM_PWW_AVAILABLE_TO_HOUR = 'INPOST_PAY_payment_apm_pww_to_time';
    private const APM_COD_PRICE = 'INPOST_PAY_payment_apm_cod';
    private const APM_COD_AVAILABLE_FROM_DAY = 'INPOST_PAY_payment_apm_cod_from_day';
    private const APM_COD_AVAILABLE_TO_DAY = 'INPOST_PAY_payment_apm_cod_to_day';
    private const APM_COD_AVAILABLE_FROM_HOUR = 'INPOST_PAY_payment_apm_cod_from_time';
    private const APM_COD_AVAILABLE_TO_HOUR = 'INPOST_PAY_payment_apm_cod_to_time';

    private const COURIER_ID = 'INPOST_PAY_payment_courier';
    private const COURIER_PWW_PRICE = 'INPOST_PAY_payment_courier_pww';
    private const COURIER_PWW_AVAILABLE_FROM_DAY = 'INPOST_PAY_payment_courier_pww_from_day';
    private const COURIER_PWW_AVAILABLE_TO_DAY = 'INPOST_PAY_payment_courier_pww_to_day';
    private const COURIER_PWW_AVAILABLE_FROM_HOUR = 'INPOST_PAY_payment_courier_pww_from_time';
    private const COURIER_PWW_AVAILABLE_TO_HOUR = 'INPOST_PAY_payment_courier_pww_to_time';
    private const COURIER_COD_PRICE = 'INPOST_PAY_payment_courier_cod';
    private const COURIER_COD_AVAILABLE_FROM_DAY = 'INPOST_PAY_payment_courier_cod_from_day';
    private const COURIER_COD_AVAILABLE_TO_DAY = 'INPOST_PAY_payment_courier_cod_to_day';
    private const COURIER_COD_AVAILABLE_FROM_HOUR = 'INPOST_PAY_payment_courier_cod_from_time';
    private const COURIER_COD_AVAILABLE_TO_HOUR = 'INPOST_PAY_payment_courier_cod_to_time';

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    /**
     * @var ObjectRepositoryInterface
     */
    private $carrierRepository;

    /**
     * @var DayFactoryInterface
     */
    private $dayFactory;

    /**
     * @var HourFactoryInterface
     */
    private $hourFactory;

    private $apmShippingOptions = [];
    private $courierShippingOptions = [];

    /**
     * @param CarrierRepository $carrierRepository
     */
    public function __construct(ShopAwareConfigurationInterface $configuration, ObjectRepositoryInterface $carrierRepository, DayFactoryInterface $dayFactory, HourFactoryInterface $hourFactory)
    {
        $this->configuration = $configuration;
        $this->carrierRepository = $carrierRepository;
        $this->dayFactory = $dayFactory;
        $this->hourFactory = $hourFactory;
    }

    public function getApmShippingOptions(int $shopId = null): Shipping
    {
        if (!isset($this->apmShippingOptions[(int) $shopId])) {
            $this->apmShippingOptions[(int) $shopId] = $this->loadApmShippingOptions($shopId);
        }

        return clone $this->apmShippingOptions[(int) $shopId];
    }

    public function getCourierShippingOptions(int $shopId = null): Shipping
    {
        if (!isset($this->courierShippingOptions[(int) $shopId])) {
            $this->courierShippingOptions[(int) $shopId] = $this->loadApmShippingOptions($shopId);
        }

        return clone $this->courierShippingOptions[(int) $shopId];
    }

    public function copy(): DTO\ShippingConfiguration
    {
        return new DTO\ShippingConfiguration(
            $this->getApmShippingOptions(),
            $this->getCourierShippingOptions()
        );
    }

    public function persist(ShippingConfigurationInterface $configuration): void
    {
        $this->setApmShippingOptions($configuration->getApmShippingOptions());
        $this->setCourierShippingOptions($configuration->getCourierShippingOptions());
    }

    private function loadApmShippingOptions(?int $shopId): Shipping
    {
        $options = new Shipping();

        $options->setCarrierId($this->loadCarrier((int) $this->configuration->get(self::APM_ID, $shopId)));
        $options->setWeekendDeliveryPrice((float) $this->configuration->get(self::APM_PWW_PRICE, $shopId));
        $options->setWeekendDeliveryAvailableFromDay($this->loadDay((int) $this->configuration->get(self::APM_PWW_AVAILABLE_FROM_DAY, $shopId)));
        $options->setWeekendDeliveryAvailableToDay($this->loadDay((int) $this->configuration->get(self::APM_PWW_AVAILABLE_TO_DAY, $shopId)));
        $options->setWeekendDeliveryAvailableFromHour($this->loadHour((int) $this->configuration->get(self::APM_PWW_AVAILABLE_FROM_HOUR, $shopId)));
        $options->setWeekendDeliveryAvailableToHour($this->loadHour((int) $this->configuration->get(self::APM_PWW_AVAILABLE_TO_HOUR, $shopId)));
        $options->setCodPrice((float) $this->configuration->get(self::APM_COD_PRICE, $shopId));
        $options->setCodAvailableFromDay($this->loadDay((int) $this->configuration->get(self::APM_COD_AVAILABLE_FROM_DAY, $shopId)));
        $options->setCodAvailableToDay($this->loadDay((int) $this->configuration->get(self::APM_COD_AVAILABLE_TO_DAY, $shopId)));
        $options->setCodAvailableFromHour($this->loadHour((int) $this->configuration->get(self::APM_COD_AVAILABLE_FROM_HOUR, $shopId)));
        $options->setCodAvailableToHour($this->loadHour((int) $this->configuration->get(self::APM_COD_AVAILABLE_TO_HOUR, $shopId)));

        return $options;
    }

    private function loadCourierShippingOptions(?int $shopId): Shipping
    {
        $options = new Shipping();

        $options->setCarrierId($this->loadCarrier((int) $this->configuration->get(self::COURIER_ID, $shopId)));
        $options->setWeekendDeliveryPrice((float) $this->configuration->get(self::COURIER_PWW_PRICE, $shopId));
        $options->setWeekendDeliveryAvailableFromDay($this->loadDay((int) $this->configuration->get(self::COURIER_PWW_AVAILABLE_FROM_DAY, $shopId)));
        $options->setWeekendDeliveryAvailableToDay($this->loadDay((int) $this->configuration->get(self::COURIER_PWW_AVAILABLE_TO_DAY, $shopId)));
        $options->setWeekendDeliveryAvailableFromHour($this->loadHour((int) $this->configuration->get(self::COURIER_PWW_AVAILABLE_FROM_HOUR, $shopId)));
        $options->setWeekendDeliveryAvailableToHour($this->loadHour((int) $this->configuration->get(self::COURIER_PWW_AVAILABLE_TO_HOUR, $shopId)));
        $options->setCodPrice((float) $this->configuration->get(self::COURIER_COD_PRICE, $shopId));
        $options->setCodAvailableFromDay($this->loadDay((int) $this->configuration->get(self::COURIER_COD_AVAILABLE_FROM_DAY, $shopId)));
        $options->setCodAvailableToDay($this->loadDay((int) $this->configuration->get(self::COURIER_COD_AVAILABLE_TO_DAY, $shopId)));
        $options->setCodAvailableFromHour($this->loadHour((int) $this->configuration->get(self::COURIER_COD_AVAILABLE_FROM_HOUR, $shopId)));
        $options->setCodAvailableToHour($this->loadHour((int) $this->configuration->get(self::COURIER_COD_AVAILABLE_TO_HOUR, $shopId)));

        return $options;
    }

    private function setApmShippingOptions(Shipping $options): void
    {
        $this->configuration->set(self::APM_ID, $options->getCarrierId());
        $this->configuration->set(self::APM_PWW_PRICE, $options->getWeekendDeliveryPrice());
        $this->configuration->set(self::APM_PWW_AVAILABLE_FROM_DAY, $options->getWeekendDeliveryAvailableFromDay());
        $this->configuration->set(self::APM_PWW_AVAILABLE_TO_DAY, $options->getWeekendDeliveryAvailableToDay());
        $this->configuration->set(self::APM_PWW_AVAILABLE_FROM_HOUR, $options->getWeekendDeliveryAvailableFromHour());
        $this->configuration->set(self::APM_PWW_AVAILABLE_TO_HOUR, $options->getWeekendDeliveryAvailableToHour());
        $this->configuration->set(self::APM_COD_PRICE, $options->getCodPrice());
        $this->configuration->set(self::APM_COD_AVAILABLE_FROM_DAY, $options->getCodAvailableFromDay());
        $this->configuration->set(self::APM_COD_AVAILABLE_TO_DAY, $options->getCodAvailableToDay());
        $this->configuration->set(self::APM_COD_AVAILABLE_FROM_HOUR, $options->getCodAvailableFromHour());
        $this->configuration->set(self::APM_COD_AVAILABLE_TO_HOUR, $options->getCodAvailableToHour());

        $this->apmShippingOptions[0] = clone $options;
    }

    private function setCourierShippingOptions(Shipping $options): void
    {
        $this->configuration->set(self::COURIER_ID, $options->getCarrierId());
        $this->configuration->set(self::COURIER_PWW_PRICE, $options->getWeekendDeliveryPrice());
        $this->configuration->set(self::COURIER_PWW_AVAILABLE_FROM_DAY, $options->getWeekendDeliveryAvailableFromDay());
        $this->configuration->set(self::COURIER_PWW_AVAILABLE_TO_DAY, $options->getWeekendDeliveryAvailableToDay());
        $this->configuration->set(self::COURIER_PWW_AVAILABLE_FROM_HOUR, $options->getWeekendDeliveryAvailableFromHour());
        $this->configuration->set(self::COURIER_PWW_AVAILABLE_TO_HOUR, $options->getWeekendDeliveryAvailableToHour());
        $this->configuration->set(self::COURIER_COD_PRICE, $options->getCodPrice());
        $this->configuration->set(self::COURIER_COD_AVAILABLE_FROM_DAY, $options->getCodAvailableFromDay());
        $this->configuration->set(self::COURIER_COD_AVAILABLE_TO_DAY, $options->getCodAvailableToDay());
        $this->configuration->set(self::COURIER_COD_AVAILABLE_FROM_HOUR, $options->getCodAvailableFromHour());
        $this->configuration->set(self::COURIER_COD_AVAILABLE_TO_HOUR, $options->getCodAvailableToHour());

        $this->courierShippingOptions[0] = clone $options;
    }

    private function loadDay(?int $id): ?Day
    {
        if (null === $id) {
            return null;
        }

        try {
            return $this->dayFactory->create($id);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    private function loadHour(?int $id): ?Hour
    {
        if (null === $id) {
            return null;
        }

        try {
            return $this->hourFactory->create($id);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    private function loadCarrier(int $referenceId): ?\Carrier
    {
        return $this->carrierRepository->findOneByReferenceId($referenceId);
    }
}
