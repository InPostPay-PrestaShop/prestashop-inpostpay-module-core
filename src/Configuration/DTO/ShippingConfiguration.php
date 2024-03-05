<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ShippingConfiguration implements ShippingConfigurationInterface
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


    public function __construct(ShippingOptions $apmOptions, ShippingOptions $courierOptions)
    {
        $this->apmShippingOptions = $apmOptions;
        $this->courierShippingOptions = $courierOptions;
    }

    public function getShippingOptions(DeliveryType $deliveryType, int $shopId = null): ShippingOptions
    {
        switch ($deliveryType) {
            case DeliveryType::Courier():
                return $this->courierShippingOptions;
            case DeliveryType::Apm():
                return $this->apmShippingOptions;
            default:
                throw new \LogicException('Not implemented.');
        }
    }

    public function getApmShippingOptions(int $shopId = null): ShippingOptions
    {
        return $this->apmShippingOptions;
    }

    public function setApmShippingOptions(ShippingOptions $shipping): self
    {
        $this->apmShippingOptions = $shipping;

        return $this;
    }

    public function getCourierShippingOptions(int $shopId = null): ShippingOptions
    {
        return $this->courierShippingOptions;
    }

    public function setCourierShippingOptions(ShippingOptions $courierShippingOptions): self
    {
        $this->courierShippingOptions = $courierShippingOptions;

        return $this;
    }
}
