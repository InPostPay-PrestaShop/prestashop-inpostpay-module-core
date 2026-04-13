<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;
use izi\prestashop\Serializer\SafeDeserializerTrait;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @implements PersistentConfigurationInterface<ShippingConfigurationInterface>
 */
final class ShippingConfiguration implements ShippingConfigurationInterface, OptionalServicesConfigurationInterface, PersistentConfigurationInterface
{
    use SafeDeserializerTrait;

    private const COURIER_SHIPPING_OPTIONS = 'INPOST_PAY_COURIER_SHIPPING_OPTIONS';
    private const APM_SHIPPING_OPTIONS = 'INPOST_PAY_APM_SHIPPING_OPTIONS';
    private const DISABLED_OPTIONAL_SERVICES = 'INPOST_PAY_DISABLED_OPTIONAL_SERVICES';

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    private $apmShippingOptions = [];
    private $courierShippingOptions = [];
    private $disabledOptionalServices = [];

    public function __construct(ShopAwareConfigurationInterface $configuration, SerializerInterface $serializer)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
    }

    public function getShippingOptions(DeliveryType $deliveryType, ?int $shopId = null): ShippingOptions
    {
        switch ($deliveryType) {
            case DeliveryType::Courier():
                return $this->getCourierShippingOptions($shopId);
            case DeliveryType::Apm():
                return $this->getApmShippingOptions($shopId);
            case DeliveryType::Digital():
                return new ShippingOptions();
            default:
                throw new \LogicException('Not implemented.');
        }
    }

    public function getApmShippingOptions(?int $shopId = null): ShippingOptions
    {
        if (!isset($this->apmShippingOptions[$key = (int) $shopId])) {
            $this->apmShippingOptions[$key] = $this->loadApmShippingOptions($shopId);
        }

        return clone $this->apmShippingOptions[$key];
    }

    public function getCourierShippingOptions(?int $shopId = null): ShippingOptions
    {
        if (!isset($this->courierShippingOptions[$key = (int) $shopId])) {
            $this->courierShippingOptions[$key] = $this->loadCourierShippingOptions($shopId);
        }

        return clone $this->courierShippingOptions[$key];
    }

    public function isServiceEnabled(string $serviceCode, ?int $shopId = null): bool
    {
        return !\in_array($serviceCode, $this->getDisabledServiceCodes($shopId), true);
    }

    public function getDisabledServiceCodes(?int $shopId = null): array
    {
        if (!isset($this->disabledOptionalServices[$key = (int) $shopId])) {
            $this->disabledOptionalServices[$key] = $this->loadDisabledOptionalServices($shopId);
        }

        return $this->disabledOptionalServices[$key];
    }

    public function copy(): DTO\ShippingConfiguration
    {
        $configuration = new DTO\ShippingConfiguration(
            $this->getApmShippingOptions(),
            $this->getCourierShippingOptions()
        );

        return $configuration->setDisabledServiceCodes($this->getDisabledServiceCodes());
    }

    public function persist(ShippingConfigurationInterface $configuration): void
    {
        $this->setApmShippingOptions($configuration->getApmShippingOptions());
        $this->setCourierShippingOptions($configuration->getCourierShippingOptions());

        if (!$configuration instanceof OptionalServicesConfigurationInterface) {
            return;
        }

        $this->setDisabledOptionalServices($configuration->getDisabledServiceCodes());
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

    private function loadDisabledOptionalServices(?int $shopId): array
    {
        $value = $this->configuration->get(self::DISABLED_OPTIONAL_SERVICES, $shopId);

        return '' === (string) $value ? [] : explode(',', $value);
    }

    private function setApmShippingOptions(ShippingOptions $options): void
    {
        $value = $this->serializer->serialize($options, 'json');
        $this->configuration->set(self::APM_SHIPPING_OPTIONS, $value);
        $this->apmShippingOptions = [clone $options];
    }

    private function setCourierShippingOptions(ShippingOptions $options): void
    {
        $value = $this->serializer->serialize($options, 'json');
        $this->configuration->set(self::COURIER_SHIPPING_OPTIONS, $value);
        $this->courierShippingOptions = [clone $options];
    }

    /**
     * @param string[] $serviceCodes
     */
    private function setDisabledOptionalServices(array $serviceCodes): void
    {
        $value = implode(',', $serviceCodes);
        $this->configuration->set(self::DISABLED_OPTIONAL_SERVICES, $value);
        $this->disabledOptionalServices = [$serviceCodes];
    }
}
