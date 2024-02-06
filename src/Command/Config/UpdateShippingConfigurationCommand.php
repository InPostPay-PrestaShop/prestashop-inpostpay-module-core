<?php

declare(strict_types=1);

namespace izi\prestashop\Command\Config;

use izi\prestashop\Configuration\DTO\Shipping;
use izi\prestashop\Configuration\ShippingAmpConfiguration;
use izi\prestashop\Configuration\ShippingAmpConfigurationInterface;
use izi\prestashop\Configuration\ShippingCourierConfiguration;
use izi\prestashop\Configuration\ShippingCourierConfigurationInterface;
use izi\prestashop\Handler\Config\UpdateShippingConfigurationHandler;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see UpdateShippingConfigurationHandler
 */
final class UpdateShippingConfigurationCommand
{
    /**
     * @var ShippingCourierConfigurationInterface
     *
     * @Assert\Valid()
     */
    private $shippingCourierConfiguration;

    /**
     * @var ShippingAmpConfigurationInterface
     *
     * @Assert\Valid()
     */
    private $shippingAmpConfiguration;

    /**
     * @param ShippingCourierConfiguration $shippingCourierConfiguration
     * @param ShippingAmpConfiguration $shippingAmpConfiguration
     */
    public function __construct(ShippingCourierConfigurationInterface $shippingCourierConfiguration, ShippingAmpConfigurationInterface $shippingAmpConfiguration)
    {
        $this->shippingCourierConfiguration = $shippingCourierConfiguration;
        $this->shippingAmpConfiguration = $shippingAmpConfiguration;
    }

    public function getShippingCourier(): Shipping
    {
        return $this->shippingCourierConfiguration->getCourierShipping();
    }

    public function setShippingCourier(Shipping $shipping): self
    {
        $this->shippingCourierConfiguration->setCourierShipping($shipping);

        return $this;
    }

    public function getShippingAmp(): Shipping
    {
        return $this->shippingAmpConfiguration->getAmpShipping();
    }

    public function setShippingAmp(Shipping $shipping): self
    {
        $this->shippingAmpConfiguration->setAmpShipping($shipping);

        return $this;
    }
}
