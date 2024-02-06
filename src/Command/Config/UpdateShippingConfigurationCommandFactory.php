<?php

declare(strict_types=1);

namespace izi\prestashop\Command\Config;

use izi\prestashop\Configuration\ShippingAmpConfiguration;
use izi\prestashop\Configuration\ShippingAmpConfigurationInterface;
use izi\prestashop\Configuration\ShippingCourierConfiguration;
use izi\prestashop\Configuration\ShippingCourierConfigurationInterface;

final class UpdateShippingConfigurationCommandFactory
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

    public function create(): UpdateShippingConfigurationCommand
    {
        return new UpdateShippingConfigurationCommand(
            $this->shippingCourierConfiguration->copy(),
            $this->shippingAmpConfiguration->copy()
        );
    }
}
