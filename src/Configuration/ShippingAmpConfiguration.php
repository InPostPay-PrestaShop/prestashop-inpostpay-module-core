<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Configuration\DTO\Shipping;
use izi\prestashop\Configuration\Factory\DayFactoryInterface;
use izi\prestashop\Configuration\Factory\HourFactoryInterface;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

final class ShippingAmpConfiguration implements ShippingAmpConfigurationInterface, PersistentConfigurationInterface
{
    use ShippingConfigurationTrait;

    private const AMP_ID = 'INPOST_PAY_payment_apm';
    private const AMP_PRICE = 'INPOST_PAY_payment_apm_pww';
    private const AMP_AVAILABLE_FROM_DAY = 'INPOST_PAY_payment_apm_pww_from_day';
    private const AMP_AVAILABLE_TO_DAY = 'INPOST_PAY_payment_apm_pww_to_day';
    private const AMP_AVAILABLE_FROM_HOUR = 'INPOST_PAY_payment_apm_pww_from_time';
    private const AMP_AVAILABLE_TO_HOUR = 'INPOST_PAY_payment_apm_pww_to_time';
    private const AMP_COD_PRICE = 'INPOST_PAY_payment_apm_cod';
    private const AMP_COD_AVAILABLE_FROM_DAY = 'INPOST_PAY_payment_apm_cod_from_day';
    private const AMP_COD_AVAILABLE_TO_DAY = 'INPOST_PAY_payment_apm_cod_to_day';
    private const AMP_COD_AVAILABLE_FROM_HOUR = 'INPOST_PAY_payment_apm_cod_from_time';
    private const AMP_COD_AVAILABLE_TO_HOUR = 'INPOST_PAY_payment_apm_cod_to_time';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    private $shippingAmp;

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

    public function copy(): DTO\ShippingAmpConfiguration
    {
        return new DTO\ShippingAmpConfiguration(
            $this->getAmpShipping()
        );
    }

    public function persist(Shipping $configuration): void
    {
        $this->configuration->set(self::AMP_ID, $configuration->getCarrierId());
        $this->configuration->set(self::AMP_PRICE, $configuration->getShippingPrice());
        $this->configuration->set(self::AMP_AVAILABLE_FROM_DAY, $configuration->getShippingAvailableFromDay());
        $this->configuration->set(self::AMP_AVAILABLE_TO_DAY, $configuration->getShippingAvailableToDay());
        $this->configuration->set(self::AMP_AVAILABLE_FROM_HOUR, $configuration->getShippingAvailableFromHour());
        $this->configuration->set(self::AMP_AVAILABLE_TO_HOUR, $configuration->getShippingAvailableToHour());
        $this->configuration->set(self::AMP_COD_PRICE, $configuration->getShippingCodPrice());
        $this->configuration->set(self::AMP_COD_AVAILABLE_FROM_DAY, $configuration->getShippingCodAvailableFromDay());
        $this->configuration->set(self::AMP_COD_AVAILABLE_TO_DAY, $configuration->getShippingCodAvailableToDay());
        $this->configuration->set(self::AMP_COD_AVAILABLE_FROM_HOUR, $configuration->getShippingCodAvailableFromHour());
        $this->configuration->set(self::AMP_COD_AVAILABLE_TO_HOUR, $configuration->getShippingCodAvailableToHour());
    }

    private function loadShipping(?int $idShop): Shipping
    {
        $shipping = new Shipping();

        $shipping->setCarrierId($this->loadCarrier((int) $this->configuration->get(self::AMP_ID, $idShop)));
        $shipping->setShippingPrice((float) $this->configuration->get(self::AMP_PRICE, $idShop));
        $shipping->setShippingAvailableFromDay($this->loadDay((int) $this->configuration->get(self::AMP_AVAILABLE_FROM_DAY, $idShop)));
        $shipping->setShippingAvailableToDay($this->loadDay((int) $this->configuration->get(self::AMP_AVAILABLE_TO_DAY, $idShop)));
        $shipping->setShippingAvailableFromHour($this->loadHour((int) $this->configuration->get(self::AMP_AVAILABLE_FROM_HOUR, $idShop)));
        $shipping->setShippingAvailableToHour($this->loadHour((int) $this->configuration->get(self::AMP_AVAILABLE_TO_HOUR, $idShop)));
        $shipping->setShippingCodPrice((float) $this->configuration->get(self::AMP_COD_PRICE, $idShop));
        $shipping->setShippingCodAvailableFromDay($this->loadDay((int) $this->configuration->get(self::AMP_COD_AVAILABLE_FROM_DAY, $idShop)));
        $shipping->setShippingCodAvailableToDay($this->loadDay((int) $this->configuration->get(self::AMP_COD_AVAILABLE_TO_DAY, $idShop)));
        $shipping->setShippingCodAvailableFromHour($this->loadHour((int) $this->configuration->get(self::AMP_COD_AVAILABLE_FROM_HOUR, $idShop)));
        $shipping->setShippingCodAvailableToHour($this->loadHour((int) $this->configuration->get(self::AMP_COD_AVAILABLE_TO_HOUR, $idShop)));

        return $shipping;
    }

    public function getAmpShipping(?int $idShop = null): Shipping
    {
        if (!isset($this->shippingAmp)) {
            $this->shippingAmp = $this->loadShipping($idShop);
        }

        return clone $this->shippingAmp;
    }

    public function setAmpShipping(Shipping $shipping): ShippingAmpConfigurationInterface
    {
        $this->shippingAmp = $shipping;

        return $this;
    }
}
