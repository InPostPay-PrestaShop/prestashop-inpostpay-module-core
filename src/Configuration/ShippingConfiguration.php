<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ShippingConfiguration implements ShippingConfigurationInterface, PersistentConfigurationInterface
{
    private const COURIER_SHIPPING_OPTIONS = 'INPOST_PAY_COURIER_SHIPPING_OPTIONS';
    private const APM_SHIPPING_OPTIONS = 'INPOST_PAY_APM_SHIPPING_OPTIONS';

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    private $apmShippingOptions = [];
    private $courierShippingOptions = [];

    public function __construct(ShopAwareConfigurationInterface $configuration, SerializerInterface $serializer)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
    }

    public function getShippingOptions(DeliveryType $deliveryType, int $shopId = null): ShippingOptions
    {
        switch ($deliveryType) {
            case DeliveryType::Courier():
                return $this->getCourierShippingOptions($shopId);
            case DeliveryType::Apm():
                return $this->getApmShippingOptions($shopId);
            default:
                throw new \LogicException('Not implemented.');
        }
    }

    public function getApmShippingOptions(int $shopId = null): ShippingOptions
    {
        if (!isset($this->apmShippingOptions[(int) $shopId])) {
            $this->apmShippingOptions[(int) $shopId] = $this->loadApmShippingOptions($shopId);
        }

        return clone $this->apmShippingOptions[(int) $shopId];
    }

    public function getCourierShippingOptions(int $shopId = null): ShippingOptions
    {
        if (!isset($this->courierShippingOptions[(int) $shopId])) {
            $this->courierShippingOptions[(int) $shopId] = $this->loadCourierShippingOptions($shopId);
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

    private function loadApmShippingOptions(?int $shopId): ShippingOptions
    {
        $value = $this->configuration->get(self::APM_SHIPPING_OPTIONS, $shopId);

        if (null !== $value && $shippingOptions = $this->deserialize($value, ShippingOptions::class)) {
            return $shippingOptions;
        }

        return new ShippingOptions();
    }

    private function loadCourierShippingOptions(?int $shopId): ShippingOptions
    {
        $value = $this->configuration->get(self::COURIER_SHIPPING_OPTIONS, $shopId);

        if (null !== $value && $shippingOptions = $this->deserialize($value, ShippingOptions::class)) {
            return $shippingOptions;
        }

        return new ShippingOptions();
    }

    private function setApmShippingOptions(ShippingOptions $options): void
    {
        $value = $this->serializer->serialize($options, 'json');
        $this->configuration->set(self::APM_SHIPPING_OPTIONS, $value);
        $this->apmShippingOptions[0] = clone $options;
    }

    private function setCourierShippingOptions(ShippingOptions $options): void
    {
        $value = $this->serializer->serialize($options, 'json');
        $this->configuration->set(self::COURIER_SHIPPING_OPTIONS, $value);
        $this->courierShippingOptions[0] = clone $options;
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    private function deserialize(string $value, string $class)
    {
        try {
            return $this->serializer->deserialize($value, $class, 'json');
        } catch (ExceptionInterface $e) {
            return null;
        }
    }
}
