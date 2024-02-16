<?php

declare(strict_types=1);

namespace izi\prestashop\Command\Config;

use izi\prestashop\Configuration\ApiConfiguration;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\GeneralConfiguration;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\OrdersConfiguration;
use izi\prestashop\Configuration\OrdersConfigurationInterface;

final class UpdateGeneralConfigurationCommandFactory
{
    /**
     * @var ApiConfigurationInterface
     */
    private $apiConfiguration;

    /**
     * @var OrdersConfigurationInterface
     */
    private $ordersConfiguration;

    /**
     * @var GeneralConfigurationInterface
     */
    private $generalConfiguration;

    /**
     * @param ApiConfiguration $apiConfiguration
     * @param OrdersConfiguration $ordersConfiguration
     * @param GeneralConfiguration $generalConfiguration
     */
    public function __construct(ApiConfigurationInterface $apiConfiguration, OrdersConfigurationInterface $ordersConfiguration, GeneralConfigurationInterface $generalConfiguration)
    {
        $this->apiConfiguration = $apiConfiguration;
        $this->ordersConfiguration = $ordersConfiguration;
        $this->generalConfiguration = $generalConfiguration;
    }

    public function create(): UpdateGeneralConfigurationCommand
    {
        return new UpdateGeneralConfigurationCommand(
            $this->apiConfiguration->copy(),
            $this->ordersConfiguration->copy(),
            $this->generalConfiguration->copy()
        );
    }
}
