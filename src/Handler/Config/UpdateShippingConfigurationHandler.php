<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateShippingConfigurationCommand;
use izi\prestashop\Configuration\ShippingAmpConfiguration;
use izi\prestashop\Configuration\ShippingAmpConfigurationInterface;
use izi\prestashop\Configuration\ShippingCourierConfiguration;
use izi\prestashop\Configuration\ShippingCourierConfigurationInterface;

final class UpdateShippingConfigurationHandler implements UpdateShippingConfigurationHandlerInterface
{
    /**
     * @var ShippingCourierConfigurationInterface
     */
    private $shippingCourierConfiguration;

    /**
     * @var ShippingAmpConfigurationInterface
     */
    private $shippingAmpConfiguration;

    /**
     * @param ShippingCourierConfiguration $shippingCourierConfiguration
     * @param ShippingAmpConfiguration $shippingAmpConfiguration
     */
    public function __construct(ShippingCourierConfiguration $shippingCourierConfiguration, ShippingAmpConfiguration $shippingAmpConfiguration)
    {
        $this->shippingCourierConfiguration = $shippingCourierConfiguration;
        $this->shippingAmpConfiguration = $shippingAmpConfiguration;
    }

    public function __invoke(UpdateShippingConfigurationCommand $command)
    {
        $this->shippingCourierConfiguration->persist($command->getShippingCourier());
        $this->shippingAmpConfiguration->persist($command->getShippingAmp());
    }
}
