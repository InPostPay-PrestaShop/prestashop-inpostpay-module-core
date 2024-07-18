<?php

declare(strict_types=1);

namespace izi\prestashop\Command\Config;

use izi\prestashop\Configuration\ApiConfiguration;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\GeneralConfiguration;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\OrdersConfiguration;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Configuration\PersistentConfigurationInterface;
use izi\prestashop\Configuration\ProductConfiguration;
use izi\prestashop\Configuration\ProductConfigurationInterface;

final class UpdateGeneralConfigurationCommandFactory
{
    /**
     * @var PersistentConfigurationInterface<ApiConfigurationInterface>
     */
    private $apiConfiguration;

    /**
     * @var PersistentConfigurationInterface<OrdersConfigurationInterface>
     */
    private $ordersConfiguration;

    /**
     * @var PersistentConfigurationInterface<GeneralConfigurationInterface>
     */
    private $generalConfiguration;

    /**
     * @var PersistentConfigurationInterface<ProductConfigurationInterface>
     */
    private $productConfiguration;

    /**
     * @param ApiConfiguration $apiConfiguration
     * @param OrdersConfiguration $ordersConfiguration
     * @param GeneralConfiguration $generalConfiguration
     * @param ProductConfiguration $productConfiguration
     */
    public function __construct(
        ApiConfigurationInterface $apiConfiguration,
        OrdersConfigurationInterface $ordersConfiguration,
        GeneralConfigurationInterface $generalConfiguration,
        ProductConfigurationInterface $productConfiguration
    ) {
        $this->apiConfiguration = $apiConfiguration;
        $this->ordersConfiguration = $ordersConfiguration;
        $this->generalConfiguration = $generalConfiguration;
        $this->productConfiguration = $productConfiguration;
    }

    public function create(): UpdateGeneralConfigurationCommand
    {
        return new UpdateGeneralConfigurationCommand(
            $this->apiConfiguration->copy(),
            $this->ordersConfiguration->copy(),
            $this->generalConfiguration->copy(),
            $this->productConfiguration->copy()
        );
    }
}
