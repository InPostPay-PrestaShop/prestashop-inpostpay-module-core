<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\ShippingConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ShippingConfiguration implements ShippingConfigurationInterface
{
    /**
     * @var Shipping
     *
     * @Assert\Valid()
     */
    private $apmShippingOptions;

    /**
     * @var Shipping
     *
     * @Assert\Valid()
     */
    private $courierShippingOptions;


    public function __construct(Shipping $apmOptions, Shipping $courierOptions)
    {
        $this->apmShippingOptions = $apmOptions;
        $this->courierShippingOptions = $courierOptions;
    }

    public function getApmShippingOptions(int $shopId = null): Shipping
    {
        return $this->apmShippingOptions;
    }

    public function setApmShippingOptions(Shipping $shipping): self
    {
        $this->apmShippingOptions = $shipping;

        return $this;
    }

    public function getCourierShippingOptions(int $shopId = null): Shipping
    {
        return $this->courierShippingOptions;
    }

    public function setCourierShippingOptions(Shipping $courierShippingOptions): self
    {
        $this->courierShippingOptions = $courierShippingOptions;

        return $this;
    }
}
