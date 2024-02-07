<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Configuration\DTO\Day;
use izi\prestashop\Configuration\DTO\Hour;
use izi\prestashop\Configuration\DTO\Shipping;
use izi\prestashop\Configuration\Factory\DayFactoryInterface;
use izi\prestashop\Configuration\Factory\HourFactoryInterface;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

final class ShippingCourierConfiguration implements ShippingCourierConfigurationInterface, PersistentConfigurationInterface
{
    use ShippingConfigurationTrait;

    private const COURIER_ID = 'INPOST_PAY_payment_courier';
    private const COURIER_PRICE = 'INPOST_PAY_payment_courier_pww';
    private const COURIER_AVAILABLE_FROM_DAY = 'INPOST_PAY_payment_courier_pww_from_day';
    private const COURIER_AVAILABLE_TO_DAY = 'INPOST_PAY_payment_courier_pww_to_day';
    private const COURIER_AVAILABLE_FROM_HOUR = 'INPOST_PAY_payment_courier_pww_from_time';
    private const COURIER_AVAILABLE_TO_HOUR = 'INPOST_PAY_payment_courier_pww_to_time';
    private const COURIER_COD_PRICE = 'INPOST_PAY_payment_courier_cod';
    private const COURIER_COD_AVAILABLE_FROM_DAY = 'INPOST_PAY_payment_courier_cod_from_day';
    private const COURIER_COD_AVAILABLE_TO_DAY = 'INPOST_PAY_payment_courier_cod_to_day';
    private const COURIER_COD_AVAILABLE_FROM_HOUR = 'INPOST_PAY_payment_courier_cod_from_time';
    private const COURIER_COD_AVAILABLE_TO_HOUR = 'INPOST_PAY_payment_courier_cod_to_time';

    /**
     * @var ConfigurationInterface
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

    private $shippingCourier;

    /**
     * @param ShopAwareConfigurationInterface $configuration
     * @param ObjectRepositoryInterface<\Carrier> $carrierRepository
     */
    public function __construct(
        ShopAwareConfigurationInterface $configuration,
        ObjectRepositoryInterface $carrierRepository,
        DayFactoryInterface $dayFactory,
        HourFactoryInterface $hourFactory
    ) {
        $this->configuration = $configuration;
        $this->carrierRepository = $carrierRepository;
        $this->dayFactory = $dayFactory;
        $this->hourFactory = $hourFactory;
    }

    public function copy(): DTO\ShippingCourierConfiguration
    {
        return new DTO\ShippingCourierConfiguration(
            $this->getCourierShipping()
        );
    }

    public function persist(Shipping $configuration): void
    {
        $this->configuration->set(self::COURIER_ID, $configuration->getCarrierId());
        $this->configuration->set(self::COURIER_PRICE, $configuration->getShippingPrice());
        $this->configuration->set(self::COURIER_AVAILABLE_FROM_DAY, $configuration->getShippingAvailableFromDay());
        $this->configuration->set(self::COURIER_AVAILABLE_TO_DAY, $configuration->getShippingAvailableToDay());
        $this->configuration->set(self::COURIER_AVAILABLE_FROM_HOUR, $configuration->getShippingAvailableFromHour());
        $this->configuration->set(self::COURIER_AVAILABLE_TO_HOUR, $configuration->getShippingAvailableToHour());
        $this->configuration->set(self::COURIER_COD_PRICE, $configuration->getShippingCodPrice());
        $this->configuration->set(self::COURIER_COD_AVAILABLE_FROM_DAY, $configuration->getShippingCodAvailableFromDay());
        $this->configuration->set(self::COURIER_COD_AVAILABLE_TO_DAY, $configuration->getShippingCodAvailableToDay());
        $this->configuration->set(self::COURIER_COD_AVAILABLE_FROM_HOUR, $configuration->getShippingCodAvailableFromHour());
        $this->configuration->set(self::COURIER_COD_AVAILABLE_TO_HOUR, $configuration->getShippingCodAvailableToHour());
    }

    public function getCourierShipping(?int $idShop = null): Shipping
    {
        if (!isset($this->shippingCourier)) {
            $this->shippingCourier = $this->loadShipping($idShop);
        }

        return clone $this->shippingCourier;
    }

    public function setCourierShipping(Shipping $shipping): ShippingCourierConfigurationInterface
    {
        $this->shippingCourier = $shipping;

        return $this;
    }

    private function loadShipping(?int $idShop): Shipping
    {
        $shipping = new Shipping();

        $shipping->setCarrierId($this->loadCarrier((int) $this->configuration->get(self::COURIER_ID, $idShop)));
        $shipping->setShippingPrice((float) $this->configuration->get(self::COURIER_PRICE, $idShop));
        $shipping->setShippingAvailableFromDay($this->loadDay((int) $this->configuration->get(self::COURIER_AVAILABLE_FROM_DAY, $idShop)));
        $shipping->setShippingAvailableToDay($this->loadDay((int) $this->configuration->get(self::COURIER_AVAILABLE_TO_DAY, $idShop)));
        $shipping->setShippingAvailableFromHour($this->loadHour((int) $this->configuration->get(self::COURIER_AVAILABLE_FROM_HOUR, $idShop)));
        $shipping->setShippingAvailableToHour($this->loadHour((int) $this->configuration->get(self::COURIER_AVAILABLE_TO_HOUR, $idShop)));
        $shipping->setShippingCodPrice((float) $this->configuration->get(self::COURIER_COD_PRICE, $idShop));
        $shipping->setShippingCodAvailableFromDay($this->loadDay((int) $this->configuration->get(self::COURIER_COD_AVAILABLE_FROM_DAY, $idShop)));
        $shipping->setShippingCodAvailableToDay($this->loadDay((int) $this->configuration->get(self::COURIER_COD_AVAILABLE_TO_DAY, $idShop)));
        $shipping->setShippingCodAvailableFromHour($this->loadHour((int) $this->configuration->get(self::COURIER_COD_AVAILABLE_FROM_HOUR, $idShop)));
        $shipping->setShippingCodAvailableToHour($this->loadHour((int) $this->configuration->get(self::COURIER_COD_AVAILABLE_TO_HOUR, $idShop)));

        return $shipping;
    }
}
