<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;
use izi\prestashop\Configuration\OptionalServicesConfigurationInterface;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ShippingConfiguration implements ShippingConfigurationInterface, OptionalServicesConfigurationInterface
{
    /**
     * @var ShippingOptions
     *
     * @Assert\Valid()
     */
    private $apmShippingOptions;

    /**
     * @var ShippingOptions
     *
     * @Assert\Valid()
     */
    private $courierShippingOptions;

    /**
     * @var array<string, string>
     *
     * @Assert\All(
     *     @Assert\Type("string"),
     *     @Assert\NotNull(),
     * )
     */
    private $disabledServiceCodes;

    public function __construct(ShippingOptions $apmOptions, ShippingOptions $courierOptions)
    {
        $this->apmShippingOptions = $apmOptions;
        $this->courierShippingOptions = $courierOptions;
    }

    public function getShippingOptions(DeliveryType $deliveryType, ?int $shopId = null): ShippingOptions
    {
        switch ($deliveryType) {
            case DeliveryType::Courier():
                return $this->courierShippingOptions;
            case DeliveryType::Apm():
                return $this->apmShippingOptions;
            case DeliveryType::Digital():
                return new ShippingOptions();
            default:
                throw new \LogicException('Not implemented.');
        }
    }

    public function getApmShippingOptions(?int $shopId = null): ShippingOptions
    {
        return $this->apmShippingOptions;
    }

    public function setApmShippingOptions(ShippingOptions $shipping): self
    {
        $this->apmShippingOptions = $shipping;

        return $this;
    }

    public function getCourierShippingOptions(?int $shopId = null): ShippingOptions
    {
        return $this->courierShippingOptions;
    }

    public function setCourierShippingOptions(ShippingOptions $courierShippingOptions): self
    {
        $this->courierShippingOptions = $courierShippingOptions;

        return $this;
    }

    public function isServiceEnabled(string $serviceCode, ?int $shopId = null): bool
    {
        return !array_key_exists($serviceCode, $this->disabledServiceCodes);
    }

    public function getDisabledServiceCodes(?int $shopId = null): array
    {
        return $this->disabledServiceCodes;
    }

    /**
     * @param string[] $serviceCodes
     *
     * @return $this
     */
    public function setDisabledServiceCodes(array $serviceCodes): self
    {
        $this->disabledServiceCodes = array_combine($serviceCodes, $serviceCodes);

        return $this;
    }

    public function isGiftWrappingEnabled(): bool
    {
        return $this->isServiceEnabled(ServiceCode::Gw()->value);
    }

    public function setGiftWrappingEnabled(bool $enabled): self
    {
        return $this->setOptionalServiceStatus(ServiceCode::Gw()->value, $enabled);
    }

    private function setOptionalServiceStatus(string $serviceCode, bool $enabled): self
    {
        if ($enabled) {
            unset($this->disabledServiceCodes[$serviceCode]);
        } else {
            $this->disabledServiceCodes[$serviceCode] = $serviceCode;
        }

        return $this;
    }
}
